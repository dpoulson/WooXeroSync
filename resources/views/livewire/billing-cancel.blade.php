<div class="bg-gray-50 p-6 rounded-xl border border-gray-200 shadow-inner dark:bg-gray-700 dark:border-gray-600 max-w-lg mx-auto mt-10">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">Checkout Process Cancelled</h2>
        <svg class="h-8 w-8 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
    </div>

    <div class="p-4 bg-yellow-100 text-yellow-700 rounded-lg text-sm border border-yellow-300">
        <h3 class="text-lg font-semibold">Subscription Abandoned</h3>
        <p class="mt-2">You have cancelled the subscription process for the **{{ $team->name ?? 'team' }}** team. No charges were made.</p>
        <p class="mt-2">Your team remains on its current plan.</p>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-4">
        <a href="{{ route('billing.checkout', $team) }}" 
            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-150 shadow-md text-center"
        >
            Try Subscribing Again
        </a>
        <a href="{{ route('dashboard') }}" 
            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-150 shadow-md text-center"
        >
            Return to Dashboard
        </a>
    </div>
</div>