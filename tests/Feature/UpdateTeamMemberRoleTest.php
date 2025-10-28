<?php

use App\Models\User;
use Laravel\Jetstream\Http\Livewire\TeamMemberManager;
use Livewire\Livewire;
use Illuminate\Support\Facades\Hash;

test('team member roles can be updated', function () {
    $this->actingAs($user = User::factory()->withFixedPassword()->withPersonalTeam()->create());

    $user->currentTeam->users()->attach(
        $otherUser = User::factory()->withFixedPassword()->create(), ['role' => 'admin']
    );

    Livewire::test(TeamMemberManager::class, ['team' => $user->currentTeam])
        ->set('managingRoleFor', $otherUser)
        ->set('currentRole', 'editor')
        ->call('updateRole');

    expect($otherUser->fresh()->hasTeamRole(
        $user->currentTeam->fresh(), 'editor'
    ))->toBeTrue();
});

test('only team owner can update team member roles', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $user->currentTeam->users()->attach(
        $otherUser = User::factory()->withFixedPassword()->create(), ['role' => 'admin']
    );

    $this->actingAs($otherUser);

    Livewire::test(TeamMemberManager::class, ['team' => $user->currentTeam])
        ->set('managingRoleFor', $otherUser)
        ->set('currentRole', 'editor')
        ->call('updateRole')
        ->assertStatus(403);

    expect($otherUser->fresh()->hasTeamRole(
        $user->currentTeam->fresh(), 'admin'
    ))->toBeTrue();
});
