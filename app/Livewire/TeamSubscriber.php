<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Team; // Your Jetstream Team Model
use Illuminate\Support\Facades\Gate;

class TeamSubscriber extends Component
{
    /**
     * The team instance passed to the component.
     */
    public Team $team;

    /**
     * The Cashier subscription name.
     */
    public string $subscriptionName = 'standard'; 

    /**
     * Mount the component with the current team.
     */
    public function mount(Team $team)
    {
        if (auth()->check() && auth()->user()->currentTeam) {
            // Get the user model instance
            $this->team = auth()->user()->currentTeam;
        } else {
            // Handle guest user case (e.g., redirect or set default state)
            abort(403, 'You must be logged in and have an active team.');
        }
    }

    /**
     * Checks if the team is currently subscribed.
     */
    public function getIsSubscribedProperty(): bool
    {
        // Use Cashier's subscribed method directly on the Billable Team model
        return $this->team->subscribed($this->subscriptionName);
    }


    public function render()
    {
        return view('livewire.team-subscriber');
    }
}