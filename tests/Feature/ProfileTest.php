<?php

test('profile page is displayed', function () {
    $this
        ->withoutVite()
        ->get('/profile')
        ->assertOk()
        ->assertSee('id="profile-root"', false);
});
