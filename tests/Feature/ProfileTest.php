<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/sistema/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create(['role' => 'cajero', 'email' => 'original.c@laratory.pe']);

    $response = $this
        ->actingAs($user)
        ->patch('/sistema/profile', [
            'name' => 'Test User',
            'email_local' => 'test.c',
            'email' => 'test.c@laratory.pe',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/sistema/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test.c@laratory.pe', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create(['role' => 'cajero', 'email' => 'same.c@laratory.pe']);

    $response = $this
        ->actingAs($user)
        ->patch('/sistema/profile', [
            'name' => 'Test User',
            'email_local' => 'same.c',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/sistema/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('profile email keeps LaraTory domain fixed', function () {
    $user = User::factory()->create(['role' => 'admin', 'email' => 'willan.a@laratory.pe']);

    $this
        ->actingAs($user)
        ->patch('/sistema/profile', [
            'name' => 'Willan',
            'email_local' => 'willan.a',
            'email' => 'willan.ops@example.com',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/sistema/profile');

    expect($user->refresh()->email)->toBe('willan.a@laratory.pe');
});

test('admin can update the editable LaraTory email alias', function () {
    $user = User::factory()->create(['role' => 'admin', 'email' => 'willan.a@laratory.pe']);

    $this
        ->actingAs($user)
        ->patch('/sistema/profile', [
            'name' => 'Willan',
            'email_local' => 'admin.a',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/sistema/profile');

    expect($user->refresh()->email)->toBe('admin.a@laratory.pe')
        ->and($user->email_verified_at)->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/sistema/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/sistema/profile')
        ->delete('/sistema/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect('/sistema/profile');

    $this->assertNotNull($user->fresh());
});
