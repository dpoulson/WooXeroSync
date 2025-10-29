<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Team;
use App\Services\WoocommerceService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class SyncTeamOrders extends Command
{
    // The name and signature of the console command.
    protected $signature = 'teams:sync-orders';

    // The console command description.
    protected $description = 'Syncs WooCommerce orders for all teams for the last 1 day.';

    public function handle()
    {
        $this->info('Starting WooCommerce order sync for all teams...');

        // Set the number of days for sync as requested
        $syncDays = Config::get('sync.team_sync_days', 1);

        // Assuming you have a 'Team' model
        $teams = Team::all();

        if ($teams->isEmpty()) {
            $this->warn('No teams found to process.');
            return 0;
        }

        foreach ($teams as $team) {
            $this->line("Processing Team ID: {$team->id} ({$team->name})...");

            try {
                // Call your service function
                WoocommerceService::SyncOrders($team, $syncDays);

                $this->info("Successfully synced orders for Team ID: {$team->id}.");
            } catch (\Exception $e) {
                // Log the error and continue to the next team
                $this->error("Failed to sync orders for Team ID: {$team->id}. Error: " . $e->getMessage());
                // You should also use Laravel's logging here:
                Log::error("Team Sync Failure: Team ID {$team->id}", ['exception' => $e]);
            }
        }

        $this->info('WooCommerce order sync complete.');

        return 0;
    }
}