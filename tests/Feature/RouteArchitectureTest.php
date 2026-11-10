<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

test('react page routes use the shared stateless web stack', function () {
    $route = Route::getRoutes()->getByName('profile');

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->not->toContain(StartSession::class)
        ->and($route->gatherMiddleware())->not->toContain(PreventRequestForgery::class)
        ->and(config('auth.defaults.guard'))->toBe('api')
        ->and(array_keys(config('auth.guards')))->not->toContain('web');
});

test('grouped routes preserve their public contracts', function (string $name, string $uri) {
    expect(Route::getRoutes()->getByName($name)?->uri())->toBe($uri);
})->with([
    ['admin.orders', 'admin/orders'],
    ['admin.users', 'admin/users'],
    ['payments.simulate', 'payments/{payment}/simulate'],
    ['api.cart.show', 'api/cart'],
    ['api.payments.store', 'api/orders/{order}/payments'],
    ['api.admin.wallet.adjustments.store', 'api/admin/users/{user}/wallet/adjustments'],
    ['api.favorites.index', 'api/favorites'],
]);

test('legacy session pages are no longer routed', function (string $uri) {
    $this->get($uri)->assertNotFound();
})->with(['/dashboard', '/forgot-password', '/confirm-password', '/verify-email']);
