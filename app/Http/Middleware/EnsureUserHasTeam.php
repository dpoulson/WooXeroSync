<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Check if the user is authenticated and has *any* team (owned or joined)
        if (Auth::check() && $user->allTeams()->count() === 0) {
            // If they have no teams, redirect them to the team creation page.
            // This is the default Jetstream route for creating a team.
            return redirect()->route('teams.create');
        }

        return $next($request);
    }
}