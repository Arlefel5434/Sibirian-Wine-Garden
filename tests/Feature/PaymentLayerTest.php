<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Str;

function paymentOrder(User $user, int $amount = 5000): Order
{
    return $user->orders()->create([
        'status' => 'pending',
        'total_amount' => $amount,
        'currency' => 'EUR',
        'delivery_info' => ['recipient_name' => 'Test Customer'],
    ]);
}

function paymentAdmin(): User
{
    $admin = User::factory()->create();
    $admin->profile->forceFill(['role' => 'admin'])->save();

    return $admin;
}

test('payment and wallet endpoints require a jwt', function () {
    $order = paymentOrder(User::factory()->create());

    $this->getJson('/api/wallet')->assertUnauthorized();
    $this->postJson("/api/orders/{$order->id}/payments", [])->assertUnauthorized();
});

test('every new customer receives an empty wallet', function () {
    $user = User::factory()->create();

    expect($user->wallet)->not->toBeNull()
        ->and($user->wallet->balance)->toBe(0)
        ->and($user->wallet->currency)->toBe('EUR');
});

test('administrator adjustments create immutable ledger entries', function () {
    $customer = User::factory()->create();
    $admin = paymentAdmin();

    $this->withToken(auth('api')->login($admin))->postJson("/api/admin/users/{$customer->id}/wallet/adjustments", [
        'direction' => 'credit',
        'amount' => 12500,
        'reason' => 'Demonstration credit',
    ])->assertOk()->assertJsonPath('data.balance', 12500);

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id' => $customer->wallet->id,
        'initiated_by' => $admin->id,
        'type' => 'admin_adjustment',
        'amount' => 12500,
        'balance_before' => 0,
        'balance_after' => 12500,
        'reason' => 'Demonstration credit',
    ]);
});

test('customers cannot adjust wallets', function () {
    $customer = User::factory()->create();

    $this->withToken(auth('api')->login($customer))->postJson("/api/admin/users/{$customer->id}/wallet/adjustments", [
        'direction' => 'credit',
        'amount' => 1000,
        'reason' => 'Unauthorized credit',
    ])->assertForbidden();
});

test('wallet payment debits once and marks the order paid', function () {
    $user = User::factory()->create();
    $user->wallet->update(['balance' => 12000]);
    $order = paymentOrder($user, 5000);
    $token = auth('api')->login($user);
    $idempotencyKey = (string) Str::uuid();
    $payload = ['provider' => 'wallet', 'idempotency_key' => $idempotencyKey];

    $this->withToken($token)->postJson("/api/orders/{$order->id}/payments", $payload)
        ->assertCreated()
        ->assertJsonPath('data.status', 'succeeded')
        ->assertJsonPath('data.amount', 5000);

    $this->withToken($token)->postJson("/api/orders/{$order->id}/payments", $payload)
        ->assertOk()
        ->assertJsonPath('data.status', 'succeeded');

    expect($user->wallet->refresh()->balance)->toBe(7000)
        ->and($order->refresh()->status)->toBe('paid')
        ->and($order->payments()->count())->toBe(1)
        ->and($user->wallet->transactions()->where('type', 'debit')->count())->toBe(1);
});

test('insufficient wallet balance rolls back the payment', function () {
    $user = User::factory()->create();
    $user->wallet->update(['balance' => 1000]);
    $order = paymentOrder($user, 5000);

    $this->withToken(auth('api')->login($user))->postJson("/api/orders/{$order->id}/payments", [
        'provider' => 'wallet',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertUnprocessable()->assertJsonValidationErrors('payment');

    expect($user->wallet->refresh()->balance)->toBe(1000)
        ->and($order->refresh()->status)->toBe('pending')
        ->and($order->payments()->count())->toBe(0);
});

test('customer cannot pay another customers order', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $order = paymentOrder($owner);

    $this->withToken(auth('api')->login($other))->postJson("/api/orders/{$order->id}/payments", [
        'provider' => 'wallet',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertNotFound();
});

test('fake gateway completes only through the local simulation flow', function () {
    $user = User::factory()->create();
    $order = paymentOrder($user);
    $token = auth('api')->login($user);

    $paymentResponse = $this->withToken($token)->postJson("/api/orders/{$order->id}/payments", [
        'provider' => 'fake',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertCreated()->assertJsonPath('data.status', 'pending');

    $paymentId = $paymentResponse->json('data.id');

    $this->withToken($token)->postJson("/api/payments/{$paymentId}/simulate", ['outcome' => 'succeeded'])
        ->assertOk()
        ->assertJsonPath('data.status', 'succeeded');

    expect($order->refresh()->status)->toBe('paid');
});

test('failed fake payment leaves the order pending for another attempt', function () {
    $user = User::factory()->create();
    $order = paymentOrder($user);
    $token = auth('api')->login($user);

    $paymentId = $this->withToken($token)->postJson("/api/orders/{$order->id}/payments", [
        'provider' => 'fake',
        'idempotency_key' => (string) Str::uuid(),
    ])->json('data.id');

    $this->withToken($token)->postJson("/api/payments/{$paymentId}/simulate", ['outcome' => 'failed'])
        ->assertOk()
        ->assertJsonPath('data.status', 'failed');

    expect($order->refresh()->status)->toBe('pending');
});

test('demo seeding provides a customer with auditable test balance', function () {
    $this->seed();

    $demo = User::query()->where('email', 'demo@example.com')->firstOrFail();

    expect($demo->wallet->balance)->toBe(250000)
        ->and($demo->wallet->transactions()->count())->toBe(1);
});
