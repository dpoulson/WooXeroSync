<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash; // <<< IMPORTANT: Add this line
use Laravel\Jetstream\Http\Livewire\LogoutOtherBrowserSessionsForm;
use Livewire\Livewire;

test('other browser sessions can be logged out', function () {
    // 1. Create the user with a known, hashed password
    $user = User::factory()->create([
        'password' => Hash::make('password'), // Hash the password 'password'
    ]);

    // 2. Act as the user
    $this->actingAs($user);

    // 3. Test the Livewire component
    Livewire::test(LogoutOtherBrowserSessionsForm::class)
        // Set the known, unhashed password for verification
        ->set('password', 'password')
        ->call('logoutOtherBrowserSessions')
        ->assertSuccessful();
});