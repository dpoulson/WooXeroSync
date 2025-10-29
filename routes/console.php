<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Models\SyncRuns;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('teams:sync-orders')
         ->hourly()
         ->withoutOverlapping();

Schedule::call(function () {
    $retentionDays = Config::get('sync.log_retention_days');
    $deletionDate = Carbon::now()->subDays($retentionDays);
    $deletedCount = SyncRuns::where('created_at', '<', $deletionDate)->delete();
    Log::info("Cleanup Task: Deleted {$deletedCount} old SyncRuns records.");

})->dailyAt('01:00'); // Run once a day at 1 AM