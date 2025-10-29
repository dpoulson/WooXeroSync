<?php

namespace App\Http\Controllers;

use App\Models\Team; // Your Jetstream Team Model
use Illuminate\Http\Request;
use Illuminate\Routing\Controller; // Use the base Controller class
use Illuminate\Support\Facades\Gate;

class TeamBillingController extends Controller
{
    /**
     * Redirects the user to the Stripe Checkout page to start a new subscription.
     * This corresponds to step 3 (Implement the Checkout).
     */
    public function checkout(Request $request, Team $team)
    {
        // 1. Authorisation Check: Ensure only the team owner or an admin can initiate checkout
        Gate::forUser($request->user())->authorize('update', $team);

        // 2. Define your Plan IDs (These must match your Stripe Dashboard Price IDs)
        // **IMPORTANT:** Replace these with your actual Stripe Price IDs.
        $priceId = 'price_1SNYQpQvE7cgoW4k1NDO2M7k'; 
        $subscriptionName = 'standard'; 

        $successPath = route('billing.success', [], false);

        // 3. Create the Stripe Checkout Session attached to the Team model
        return $team->newSubscription($subscriptionName, $priceId)
            // Optional: If you want to use promotion codes
            ->allowPromotionCodes() 
            ->checkout([
                // Success URL must use {CHECKOUT_SESSION_ID} for Cashier to work correctly
                'success_url' => url($successPath . '?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => route('billing.cancel', ['team' => $team->id]),
            ]);
    }
    
    // ---

    /**
     * Redirects the user to the Stripe Customer Billing Portal.
     * This corresponds to step 4 (Implement Billing Portal).
     */
    public function portal(Request $request, Team $team)
    {
        // 1. Authorisation Check: Ensure only the team owner or an admin can manage billing
        Gate::forUser($request->user())->authorize('update', $team);

        // 2. Redirect the Billable Team entity to the portal.
        // The return URL is where the user goes after leaving the Stripe portal.
        return $team->redirectToBillingPortal(route('dashboard'));
    }
}