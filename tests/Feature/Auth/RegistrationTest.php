<?php

test('public registration is disabled', function () {
    $response = $this->get('/register');

    $response->assertNotFound();
});

test('new users cannot register publicly', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    $response->assertNotFound();
});
