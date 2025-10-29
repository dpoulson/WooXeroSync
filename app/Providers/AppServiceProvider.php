<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Routing\Middleware\SubstituteBindings;
use App\Http\Middleware\EnsureUserHasTeam;
use Laravel\Cashier\Cashier;
use App\Models\Team;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashier::useCustomerModel(Team::class);
        $this->app['router']->aliasMiddleware('hasTeam', EnsureUserHasTeam::class);
    }
}
