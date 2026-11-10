<?php

test('login screen can be rendered', function () {
    $this->withoutVite();

    $response = $this->get('/login');

    $response
        ->assertOk()
        ->assertSee('auth-root');
});
