<?php

namespace App\Livewire;

use Livewire\Component;
use Laravel\Jetstream\Jetstream;
use Livewire\Attributes\Url; // Optional: If you want to persist the selection in the URL

class TeamList extends Component
{
    public $teams; // We can keep this, but it will be set in render()
    public $selectedTeamId = null;

    // Remove the mount method, or clear it if you had other logic
    // public function mount() {}

    /**
     * Dispatches an event when a team row is clicked.
     */
    public function selectTeam($teamId)
    {
        // 1. Highlight the selected row (this causes the component to refresh)
        $this->selectedTeamId = $teamId;

        // 2. Dispatch the event
        $this->dispatch('team-selected', $teamId);
    }
    
    /**
     * Render the component's view and fetch fresh data.
     */
    public function render()
    {
        $teamModel = Jetstream::teamModel();

        // Fetch all teams and eagerly load the count of users *every time* it renders
        // This ensures the users_count is present after any subsequent update/refresh.
        $this->teams = $teamModel::withCount('users')->get();
        
        return view('livewire.team-list');
    }
}