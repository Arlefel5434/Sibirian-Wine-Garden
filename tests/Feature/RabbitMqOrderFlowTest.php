<?php

use App\Jobs\ProcessOrder;
use App\Jobs\SendOrderConfirmationEmail;
use App\Jobs\SubmitOrderToDelivery;
use App\Models\OutboxMessage;
use App\Models\ProcessedMessage;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderProcessed;
use App\Services\DeliveryService;
use App\Services\IdempotentMessageProcessor;
use App\Services\OrderFailureService;
use App\Services\OrderWorkflowService;
use App\Services\OutboxPublisher;
use App\Services\OutboxService;
use App\Services\ProcessOrderService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

function queuedCheckoutPayload(string $idempotencyKey): array
{
    return [
        'idempotency_key' => $idempotencyKey,
        'recipient_name' => 'Queue Customer',
        'country_code' => 'DE',
        'city' => 'Berlin',
        'postal_code' => '10115',
        'address_line' => 'Broker street 1',
    ];
}

function queuedCart(User $user, int $stock = 5, int $price = 5000): array
{
    $product = Product::factory()->create(['status' => 'active']);
    $sku = $product->skus()->create([
        'sku' => fake()->unique()->bothify('QUEUE-####'),
        'name' => 'Bottle',
        'stock_quantity' => $stock,
        'price' => $price,
        'currency' => 'EUR',
        'quantity_unit' => 'piece',
    ]);
    $user->cart()->create()->items()->create(['sku_id' => $sku->id, 'quantity' => 1]);

    return [$product, $sku];
}

test('checkout atomically reserves stock and wallet funds with an outbox message', function () {
    $user = User::factory()->create();
    $user->wallet->update(['balance' => 12000]);
    [, $sku] = queuedCart($user);
    $key = (string) Str::uuid();

    $response = $this->withToken(auth('api')->login($user))->postJson('/api/checkout', queuedCheckoutPayload($key));

    $response->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.payments.0.status', 'pending');

    $orderId = $response->json('data.id');

    expect($sku->refresh()->stock_quantity)->toBe(4)
        ->and($user->wallet->refresh()->balance)->toBe(12000)
        ->and($user->wallet->availableBalance())->toBe(7000);

    $this->assertDatabaseHas('wallet_reservations', ['order_id' => $orderId, 'status' => 'reserved', 'amount' => 5000]);
    $this->assertDatabaseHas('outbox_messages', ['type' => 'OrderCreated', 'aggregate_id' => $orderId]);
});

test('checkout idempotency returns the original order after the cart was cleared', function () {
    $user = User::factory()->create();
    $user->wallet->update(['balance' => 12000]);
    queuedCart($user);
    $key = (string) Str::uuid();
    $token = auth('api')->login($user);

    $first = $this->withToken($token)->postJson('/api/checkout', queuedCheckoutPayload($key))->assertCreated();
    $second = $this->withToken($token)->postJson('/api/checkout', queuedCheckoutPayload($key))->assertOk();

    expect($second->json('data.id'))->toBe($first->json('data.id'))
        ->and($user->orders()->count())->toBe(1)
        ->and(OutboxMessage::query()->where('type', 'OrderCreated')->count())->toBe(1);
});

test('checkout rolls back when available wallet funds are insufficient', function () {
    $user = User::factory()->create();
    $user->wallet->update(['balance' => 1000]);
    [, $sku] = queuedCart($user);

    $this->withToken(auth('api')->login($user))
        ->postJson('/api/checkout', queuedCheckoutPayload((string) Str::uuid()))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('payment');

    expect($sku->refresh()->stock_quantity)->toBe(5)
        ->and($user->orders()->count())->toBe(0)
        ->and(OutboxMessage::query()->count())->toBe(0);
});

test('outbox publisher dispatches the order job and marks the message published', function () {
    Queue::fake();
    $user = User::factory()->create();
    $user->wallet->update(['balance' => 12000]);
    queuedCart($user);
    $this->withToken(auth('api')->login($user))->postJson('/api/checkout', queuedCheckoutPayload((string) Str::uuid()))->assertCreated();

    expect(app(OutboxPublisher::class)->publish())->toBe(['published' => 1, 'failed' => 0]);

    Queue::assertPushed(ProcessOrder::class, fn (ProcessOrder $job) => $job->orderId === $user->orders()->first()->id);
    expect(OutboxMessage::query()->firstOrFail()->published_at)->not->toBeNull();
});

test('outbox keeps messages pending when the broker is unavailable', function () {
    $user = User::factory()->create();
    $user->wallet->update(['balance' => 12000]);
    queuedCart($user);
    $this->withToken(auth('api')->login($user))->postJson('/api/checkout', queuedCheckoutPayload((string) Str::uuid()))->assertCreated();
    Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('Broker unavailable'));

    expect(app(OutboxPublisher::class)->publish())->toBe(['published' => 0, 'failed' => 1]);

    $message = OutboxMessage::query()->firstOrFail();

    expect($message->published_at)->toBeNull()
        ->and($message->failed_at)->toBeNull()
        ->and($message->attempts)->toBe(1)
        ->and($message->last_error)->toBe('Broker unavailable')
        ->and($user->orders()->firstOrFail()->status)->toBe('pending');
});

test('invalid outbox messages fail permanently until explicitly retried', function () {
    $message = app(OutboxService::class)->record('UnsupportedMessage', User::class, 1, ['order_id' => 1]);

    expect(app(OutboxPublisher::class)->publish())->toBe(['published' => 0, 'failed' => 1]);

    $message->refresh();

    expect($message->failed_at)->not->toBeNull()
        ->and($message->attempts)->toBe(1)
        ->and($message->last_error)->toContain('Unsupported outbox message type')
        ->and(app(OutboxPublisher::class)->publish())->toBe(['published' => 0, 'failed' => 0]);

    $this->artisan('outbox:retry', ['id' => $message->id])->assertSuccessful();

    expect($message->refresh()->failed_at)->toBeNull()
        ->and($message->attempts)->toBe(0)
        ->and($message->last_error)->toBeNull();
});

test('order processing captures funds exactly once and creates downstream work', function () {
    Notification::fake();
    $user = User::factory()->create();
    $user->wallet->update(['balance' => 12000]);
    queuedCart($user);
    $this->withToken(auth('api')->login($user))->postJson('/api/checkout', queuedCheckoutPayload((string) Str::uuid()))->assertCreated();
    $order = $user->orders()->firstOrFail();
    $created = OutboxMessage::query()->where('type', 'OrderCreated')->firstOrFail();

    app(ProcessOrderService::class)->process($created->id, $order->id);
    app(ProcessOrderService::class)->process($created->id, $order->id);

    expect($order->refresh()->status)->toBe('paid')
        ->and($user->wallet->refresh()->balance)->toBe(7000)
        ->and($user->wallet->transactions()->where('type', 'debit')->count())->toBe(1)
        ->and($order->workflowSteps()->count())->toBe(2)
        ->and(OutboxMessage::query()->count())->toBe(3);

    $confirmation = OutboxMessage::query()->where('type', 'OrderConfirmationEmailRequested')->firstOrFail();
    $delivery = OutboxMessage::query()->where('type', 'DeliverySubmissionRequested')->firstOrFail();
    $messages = app(IdempotentMessageProcessor::class);
    $workflow = app(OrderWorkflowService::class);

    (new SendOrderConfirmationEmail($confirmation->id, $order->id))->handle($messages, $workflow);
    (new SubmitOrderToDelivery($delivery->id, $order->id))->handle($messages, app(DeliveryService::class), $workflow);

    Notification::assertSentTo($user, OrderProcessed::class);
    expect($order->refresh()->status)->toBe('processing')
        ->and($order->deliverySubmission)->not->toBeNull()
        ->and($order->deliverySubmission->status)->toBe('submitted');
});

test('terminal order processing failure releases reservations and restores stock', function () {
    $user = User::factory()->create();
    $user->wallet->update(['balance' => 12000]);
    [, $sku] = queuedCart($user);
    $this->withToken(auth('api')->login($user))->postJson('/api/checkout', queuedCheckoutPayload((string) Str::uuid()))->assertCreated();
    $order = $user->orders()->firstOrFail();

    app(OrderFailureService::class)->cancelPending($order->id);
    app(OrderFailureService::class)->cancelPending($order->id);

    expect($order->refresh()->status)->toBe('cancelled')
        ->and($sku->refresh()->stock_quantity)->toBe(5)
        ->and($order->walletReservation->status)->toBe('released')
        ->and($order->payments()->firstOrFail()->status)->toBe('failed');
});

test('email failure is recorded without blocking successful delivery processing', function () {
    $user = User::factory()->create();
    $user->wallet->update(['balance' => 12000]);
    queuedCart($user);
    $this->withToken(auth('api')->login($user))->postJson('/api/checkout', queuedCheckoutPayload((string) Str::uuid()))->assertCreated();
    $order = $user->orders()->firstOrFail();
    $created = OutboxMessage::query()->where('type', 'OrderCreated')->firstOrFail();

    app(ProcessOrderService::class)->process($created->id, $order->id);

    $confirmation = OutboxMessage::query()->where('type', 'OrderConfirmationEmailRequested')->firstOrFail();
    $delivery = OutboxMessage::query()->where('type', 'DeliverySubmissionRequested')->firstOrFail();
    (new SendOrderConfirmationEmail($confirmation->id, $order->id))->failed(new RuntimeException('SMTP unavailable'));
    (new SubmitOrderToDelivery($delivery->id, $order->id))->handle(
        app(IdempotentMessageProcessor::class),
        app(DeliveryService::class),
        app(OrderWorkflowService::class),
    );

    $notificationStep = $order->workflowSteps()->where('name', 'notification')->firstOrFail();

    expect($notificationStep->status)->toBe('failed')
        ->and($notificationStep->failed_at)->not->toBeNull()
        ->and($notificationStep->last_error)->toBe('SMTP unavailable')
        ->and($order->refresh()->status)->toBe('processing');
});

test('processed message lookup remains idempotent after its outbox row was pruned', function () {
    $messageId = (string) Str::uuid();
    ProcessedMessage::query()->create([
        'consumer' => 'process-order',
        'message_id' => $messageId,
        'processed_at' => now(),
    ]);
    $handled = false;

    $processed = app(IdempotentMessageProcessor::class)->process('process-order', $messageId, function () use (&$handled) {
        $handled = true;
    });

    expect($processed)->toBeFalse()
        ->and($handled)->toBeFalse();
});

test('outbox pruning removes only old messages confirmed as processed', function () {
    $processedMessage = app(OutboxService::class)->record('OrderCreated', User::class, 1, ['order_id' => 1]);
    $processedMessage->update(['published_at' => now()->subDays(31)]);
    ProcessedMessage::query()->create([
        'consumer' => 'process-order',
        'message_id' => $processedMessage->id,
        'processed_at' => now()->subDays(31),
    ]);
    $unprocessedMessage = app(OutboxService::class)->record('OrderCreated', User::class, 2, ['order_id' => 2]);
    $unprocessedMessage->update(['published_at' => now()->subDays(31)]);
    $recentMessage = app(OutboxService::class)->record('OrderCreated', User::class, 3, ['order_id' => 3]);
    $recentMessage->update(['published_at' => now()]);
    ProcessedMessage::query()->create([
        'consumer' => 'process-order',
        'message_id' => $recentMessage->id,
        'processed_at' => now(),
    ]);

    $this->artisan('outbox:prune', ['--days' => 30])->assertSuccessful();

    $this->assertDatabaseMissing('outbox_messages', ['id' => $processedMessage->id]);
    $this->assertDatabaseMissing('processed_messages', ['message_id' => $processedMessage->id]);
    $this->assertDatabaseHas('outbox_messages', ['id' => $unprocessedMessage->id]);
    $this->assertDatabaseHas('outbox_messages', ['id' => $recentMessage->id]);
});
