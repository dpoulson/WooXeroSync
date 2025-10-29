<div>
    {{-- Error Flash Message (e.g., from an attempted checkout) --}}
    @if (session()->has('error'))
        <div class="p-4 mb-4 bg-red-100 text-red-700 rounded-lg text-sm border border-red-300">
            {{ session('error') }}
        </div>
    @endif
    
    {{-- Main Container styled to match your existing design --}}
    <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 shadow-inner dark:bg-gray-700 dark:border-gray-600">

        @if ($team->subscribed('standard') || $team->onTrial('standard'))
            {{-- Status: Subscribed --}}
            <div class="p-4 bg-green-100 text-green-700 rounded-lg text-sm border border-green-300 text-center">
                <h3 class="text-xl font-semibold flex items-center justify-center">
                    <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                    Subscription Active!
                </h3>
                <p class="mt-2">The organisation {{ $team->name }} is currently on the Standard plan.</p>
                <a href="{{ route('billing.portal', $team) }}" 
                   class="mt-4 inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-150 shadow-md text-sm">
                    Manage Billing
                </a>
            </div>
        @elseif ($team->is_permanent_free)
        <div class="p-4 bg-green-100 text-green-700 rounded-lg text-sm border border-green-300 text-center">
            <h3 class="text-xl font-semibold flex items-center justify-center">
                <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                Subscription Active!
            </h3>
            <p class="mt-2">The organisation {{ $team->name }} is currently permanently subscribed.</p>
        </div>            
        @else
            {{-- Status: Not Subscribed --}}
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">Standard Plan Subscription</h2>
                <svg class="h-8 w-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c1.657 0 3 .895 3 2s-1.343 2-3 2-3 .895-3 2 1.343 2 3 2m0-8h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            
            <div class="p-4 bg-yellow-100 text-yellow-700 rounded-lg text-sm border border-yellow-300">
                The organisation {{ $team->name }} does not currently have a valid subscription. Upgrade now to unlock all features.
            </div>

            <p class="mt-4 text-gray-700 dark:text-gray-300">
                **Plan:** Standard (Unlimited users, advanced features)
            </p>
            <p class="text-gray-700 dark:text-gray-300">
                **Price:** £5/month.
            </p>

            {{-- The key change: Use a standard anchor tag linked to the controller route --}}
            <a href="{{ route('billing.checkout', $team) }}" 
                class="mt-6 inline-block bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-150 shadow-md w-full text-center"
            >
                Subscribe
            </a>
        @endif
    </div>
</div>