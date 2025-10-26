<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="flex flex-col">
    
        <div class="h-1/3 grid grid-cols-2 gap-4 mb-4"> 
            <div class="bg-white p-6 rounded-lg shadow-md">
                @livewire('xero-connection-status')
            </div>
    
            <div class="bg-white p-6 rounded-lg shadow-md">
                @livewire('woocommerce-connection-status')
            </div>
        </div>
    
        <div class="flex-grow bg-white p-6 rounded-lg shadow-md overflow-y-auto">
            @livewire('run-manual-sync')
        </div>
    </div>
</x-app-layout>