<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Team; // Ensure you import your Team model

class GrantPermanentSubscription extends Command
{
    protected $signature = 'subscription:grant-permanent {team_id}';

    protected $description = 'Grants a permanent free subscription to a team by setting trial_ends_at to null.';

    public function handle()
    {
        $team = Team::find($this->argument('team_id'));

        if (!$team) {
            $this->error("Team ID {$this->argument('team_id')} not found.");
            return 1;
        }

        // Set the trial_ends_at column to NULL
        $team->forceFill([
            'trial_ends_at' => null,
            'is_permanent_free' => true,
            // You might also add a flag like 'is_permanent_free' => true
        ])->save();

        $this->info("Team '{$team->name}' (ID: {$team->id}) has been granted a permanent subscription.");
        return 0;
    }
}