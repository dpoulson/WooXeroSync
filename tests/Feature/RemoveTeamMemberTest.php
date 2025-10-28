<?php

use App\Models\User;
use Laravel\Jetstream\Http\Livewire\TeamMemberManager;
use Livewire\Livewire;

test('team members can be removed from teams', function () {
    $this->actingAs($user = User::factory()->withFixedPassword()->withPersonalTeam()->create());

    $user->currentTeam->users()->attach(
        $otherUser = User::factory()->withFixedPassword()->create(), ['role' => 'admin']
    );

    Livewire::test(TeamMemberManager::class, ['team' => $user->currentTeam])
        ->set('teamMemberIdBeingRemoved', $otherUser->id)
        ->call('removeTeamMember');

    expect($user->currentTeam->fresh()->users)->toHaveCount(0);
});

test('only team owner can remove team members', function () {
    $user = User::factory()->withFixedPassword()->withPersonalTeam()->create();

    $user->currentTeam->users()->attach(
        $otherUser = User::factory()->withFixedPassword()->create(), ['role' => 'admin']
    );

    $this->actingAs($otherUser);

    Livewire::test(TeamMemberManager::class, ['team' => $user->currentTeam])
        ->set('teamMemberIdBeingRemoved', $user->id)
        ->call('removeTeamMember')
        ->assertStatus(403);
});
