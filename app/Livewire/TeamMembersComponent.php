<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Laravel\Jetstream\Jetstream;

class TeamMembersComponent extends Component
{
    public $teamId = null;
    public $members = null; 
    public $team = null;

    public $selectedUserId = null;

    #[On('team-selected')]
    public function loadMembers($id)
    {
        $this->teamId = $id;

        $teamModel = Jetstream::teamModel();
        
        // 1. Fetch the team model, eager loading the two required relationships:
        // 'owner' (the single owner) and 'users' (the members excluding the owner).
        $this->team = $teamModel::with(['owner', 'users'])->find($id);

        // If the team exists...
        if ($this->team) {
            
            // ✨ THE FINAL FIX: Manually merge the 'owner' and 'users' collections.
            // This creates the 'allMembers' collection safely within Livewire.
            // It relies on $this->team->users and $this->team->owner being loaded.
            $this->members = $this->team->users->merge([$this->team->owner]); 

        } else {
            // Reset if team is not found
            $this->teamId = null;
            $this->members = collect(); // Ensure $members is an empty Collection, not null
        }
    }

    /**
     * Dispatches an event when a user is clicked.
     * @param int $userId The ID of the selected User.
     */
    public function selectUser($userId)
    {
        // 1. Highlight the selected row
        $this->selectedUserId = $userId;

        // 2. Dispatch the event that the UserDetailComponent is listening for
        $this->dispatch('user-selected', $userId);
    }

    public function render()
    {
        return view('livewire.team-members-component');
    }
}