<div class="bg-gray-50 p-6 rounded-xl border border-gray-200 shadow-inner dark:bg-gray-700 dark:border-gray-600 max-w-lg mx-auto mt-10">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">Subscription Status</h2>
        <svg class="h-8 w-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>

    @if ($status === 'processing')
        <div class="p-4 text-center">
            {{-- Loading Spinner with Primary Color --}}
            <svg class="animate-spin h-8 w-8 text-indigo-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-4 text-lg font-medium text-gray-700 dark:text-gray-300">Verifying your new subscription...</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Please do not close this window.</p>
        </div>
    @elseif ($status === 'success')
        <div class="p-4 bg-green-100 text-green-700 rounded-lg text-sm border border-green-300">
            <h3 class="text-lg font-semibold flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Subscription Activated!
            </h3>
            <p class="mt-2">Your **{{ $team->name ?? 'team' }}** team is now fully subscribed. You can now access all premium features.</p>
        </div>
        
        <a href="{{ route('dashboard') }}" 
            class="mt-6 inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-150 shadow-md w-full text-center"
        >
            Go to Dashboard
        </a>
    @else
        {{-- Failed / Error Status --}}
        <div class="p-4 bg-red-100 text-red-700 rounded-lg text-sm border border-red-300">
            <h3 class="text-lg font-semibold flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                Payment Failed
            </h3>
            <p class="mt-2">There was an issue processing your payment. Please try again or visit the billing portal to update your details.</p>
        </div>
        
        <a href="{{ route('billing.portal', $team) }}" 
            class="mt-6 inline-block bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-150 shadow-md w-full text-center"
        >
            Manage Payment Details
        </a>
    @endif
</div>