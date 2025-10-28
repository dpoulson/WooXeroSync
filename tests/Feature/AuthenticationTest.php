<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    // FIX: Explicitly set the password on creation
    $user = User::factory()->create([
        'password' => Hash::make('password'), // Ensure it's the correct hash
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password', // Unhashed password for the login post
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users cannot authenticate with invalid password', function () {
    // 🔑 FIX: Explicitly set the password to its hashed value
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});
