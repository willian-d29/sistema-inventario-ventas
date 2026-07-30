<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create(['email' => 'tester.c@laratory.pe']);

    $response = $this->post('/login', [
        'email' => 'tester.c',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/sistema/dashboard');
});

test('users can authenticate with LaraTory email alias', function () {
    $user = User::factory()->create(['role' => 'admin', 'email' => 'willan.a@laratory.pe']);

    $response = $this->post('/login', [
        'email' => 'willan.a',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/sistema/dashboard');
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create(['email' => 'wrong.c@laratory.pe']);

    $this->post('/login', [
        'email' => 'wrong.c',
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
