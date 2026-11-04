<?php

use App\Models\User;

test('profile update requires authentication', function () {
    $this
        ->patchJson('/api/profile', [
            'email' => 'customer@example.com',
            'phone_number' => '+79991234567',
        ])
        ->assertUnauthorized();
});

test('customers can update their email and phone number', function () {
    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this
        ->withToken($token)
        ->patchJson('/api/profile', [
            'email' => 'updated@example.com',
            'phone_number' => '+79991234567',
        ])
        ->assertOk()
        ->assertJsonPath('data.email', 'updated@example.com')
        ->assertJsonPath('data.phone_number', '+79991234567');

    expect($user->refresh()->email)
        ->toBe('updated@example.com')
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->profile->phone_number)->toBe('+79991234567');
});

test('profile update validates email uniqueness and phone format', function () {
    $existing = User::factory()->create();
    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this
        ->withToken($token)
        ->patchJson('/api/profile', [
            'email' => $existing->email,
            'phone_number' => 'invalid phone',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'phone_number']);
});
