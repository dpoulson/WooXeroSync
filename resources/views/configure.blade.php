<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Configure') }}
        </h2>
    </x-slot>

    @php
        $currentTeam = Auth::user()->currentTeam
    @endphp


    <div class="flex flex-col">
        @if ($currentTeam->subscribed('standard') || $currentTeam->onTrial())
            @if ($currentTeam->onTrial())
                @php    
                    // Calculate days remaining
                    $daysRemaining = (int)now()->diffInDays($currentTeam->trial_ends_at, false); // false = return a negative number if trial is over
                @endphp
    
                {{-- Only show the banner if the trial hasn't technically ended today or before --}}
                @if ($daysRemaining >= 0)
                    <div class="bg-yellow-500 text-white font-semibold text-center py-2 px-4 shadow-md gap-2 mb-2 rounded-lg">
                        Your free trial ends in **{{ $daysRemaining }}** {{ Str::plural('day', $daysRemaining) }}.
                        <a href="{{ route('billing.checkout', $currentTeam) }}" class="underline hover:text-yellow-100 ml-2">Upgrade now.</a>
                    </div>
                @endif
            @endif
    
        <div class="h-1/3 grid grid-cols-2 gap-2 mb-2"> 
            <div class="bg-white p-4 rounded-lg shadow-md">
                @livewire('payment-mapping')
            </div>
    
            <div class="bg-white p-4 rounded-lg shadow-md">
                @livewire('revenue-mapping')
            </div>
        </div>

        <div class="h-1/3 grid grid-cols-2 gap-2 mb-2"> 
            <div class="bg-white p-4 rounded-lg shadow-md">
                @livewire('team-subscriber')
            </div>
    
            <div class="bg-white p-4 rounded-lg shadow-md">

            </div>
        </div>
        @else
        <div class="flex-grow bg-white p-4 rounded-lg shadow-md overflow-y-auto">
            You currently have no subscription
            <a href="{{ route('billing.checkout', $currentTeam) }}" 
            class="mt-6 inline-block bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-150 shadow-md w-full text-center"
        >
            Subscribe
        </a>
        </div>
    @endif
    
    </div>
</x-app-layout>