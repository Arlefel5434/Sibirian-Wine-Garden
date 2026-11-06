<?php

use App\Models\User;

function dashboardUser(string $role): User
{
    $user = User::factory()->create();
    $user->profile->forceFill(['role' => $role])->save();

    return $user;
}

test('admin tables require authentication', function () {
    $this->getJson('/api/admin/orders')->assertUnauthorized();
    $this->getJson('/api/admin/users')->assertUnauthorized();
    $this->patchJson('/api/admin/users/1', ['role' => 'admin'])->assertUnauthorized();
});

test('customers and content managers cannot access admin tables', function (string $role) {
    $user = dashboardUser($role);
    $token = auth('api')->login($user);

    $this->withToken($token)->getJson('/api/admin/orders')->assertForbidden();
    $this->withToken($token)->getJson('/api/admin/users')->assertForbidden();
    $this->withToken($token)->patchJson("/api/admin/users/{$user->id}", ['role' => 'admin'])->assertForbidden();
})->with(['customer', 'content_manager']);

test('administrators can review every order', function () {
    $admin = dashboardUser('admin');
    $customer = dashboardUser('customer');
    $order = $customer->orders()->create([
        'status' => 'pending',
        'total_amount' => 5500,
        'currency' => 'EUR',
        'delivery_info' => ['recipient_name' => 'Customer'],
    ]);

    $this->withToken(auth('api')->login($admin))
        ->getJson('/api/admin/orders')
        ->assertOk()
        ->assertJsonPath('data.0.id', $order->id)
        ->assertJsonPath('data.0.customer.id', $customer->id)
        ->assertJsonPath('data.0.total_amount', 5500);
});

test('administrators can review users with roles and order counts', function () {
    $admin = dashboardUser('admin');
    $customer = dashboardUser('customer');
    $customer->orders()->create([
        'status' => 'paid',
        'total_amount' => 2500,
        'currency' => 'EUR',
        'delivery_info' => ['recipient_name' => 'Customer'],
    ]);

    $this->withToken(auth('api')->login($admin))
        ->getJson('/api/admin/users')
        ->assertOk()
        ->assertJsonFragment([
            'id' => $customer->id,
            'role' => 'customer',
            'is_banned' => false,
            'orders_count' => 1,
        ]);
});

test('administrators can change another users role', function () {
    $admin = dashboardUser('admin');
    $customer = dashboardUser('customer');

    $this->withToken(auth('api')->login($admin))
        ->patchJson("/api/admin/users/{$customer->id}", ['role' => 'content_manager'])
        ->assertOk()
        ->assertJsonPath('data.role', 'content_manager');

    expect($customer->profile->refresh()->role)->toBe('content_manager');
});

test('administrators can block and unblock another user', function () {
    $admin = dashboardUser('admin');
    $customer = dashboardUser('customer');
    $login = $this->postJson('/api/auth/login', [
        'email' => $customer->email,
        'password' => 'password',
    ])->assertOk();
    $customerToken = $login->json('access_token');
    $adminToken = auth('api')->login($admin);

    $this->withToken($adminToken)
        ->patchJson("/api/admin/users/{$customer->id}", [
            'banned' => true,
            'ban_reason' => 'Repeated abuse',
        ])
        ->assertOk()
        ->assertJsonPath('data.is_banned', true)
        ->assertJsonPath('data.ban_reason', 'Repeated abuse');

    $this->assertDatabaseHas('user_bans', [
        'user_id' => $customer->id,
        'banned_by' => $admin->id,
        'reason' => 'Repeated abuse',
    ]);
    $this->assertDatabaseMissing('refresh_tokens', [
        'user_id' => $customer->id,
        'revoked_at' => null,
    ]);
    auth('api')->setToken($customerToken)->forgetUser();
    $this->withToken($customerToken)->getJson('/api/auth/me')->assertUnauthorized();
    auth('api')->forgetUser();
    $this->postJson('/api/auth/login', [
        'email' => $customer->email,
        'password' => 'password',
    ])->assertUnauthorized();

    auth('api')->setToken($adminToken)->forgetUser();
    $this->withToken($adminToken)
        ->patchJson("/api/admin/users/{$customer->id}", ['banned' => false])
        ->assertOk()
        ->assertJsonPath('data.is_banned', false);

    $this->assertDatabaseMissing('user_bans', ['user_id' => $customer->id]);
    auth('api')->forgetUser();
    $this->postJson('/api/auth/login', [
        'email' => $customer->email,
        'password' => 'password',
    ])->assertOk();
});

test('administrators cannot change their own role or block themselves', function () {
    $admin = dashboardUser('admin');
    $token = auth('api')->login($admin);

    $this->withToken($token)
        ->patchJson("/api/admin/users/{$admin->id}", ['role' => 'customer'])
        ->assertForbidden();

    $this->withToken($token)
        ->patchJson("/api/admin/users/{$admin->id}", ['banned' => true])
        ->assertForbidden();
});

test('admin table pages render their React mounts', function () {
    $this->withoutVite()->get('/admin/orders')->assertOk()->assertSee('id="admin-orders-root"', false);
    $this->withoutVite()->get('/admin/users')->assertOk()->assertSee('id="admin-users-root"', false);
});
