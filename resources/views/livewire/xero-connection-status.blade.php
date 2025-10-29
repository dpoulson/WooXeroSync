<div class="bg-gray-50 p-4 rounded-xl border border-gray-200 shadow-inner dark:bg-gray-700 dark:border-gray-600">
    
    {{-- Header Section --}}
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">Xero Connection</h2>
        <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
    </div>
        @php
            $isConnected = $xeroStatus['connected'] ?? false;
        @endphp
        
        {{--------------------------------------------------}}
        {{-- Connected State (IF) --}}
        {{--------------------------------------------------}}
        @if ($isConnected)
            
            {{-- Connection Status Tag --}}
            <div class="flex items-center space-x-3 mb-6 p-3 bg-green-50 rounded-lg border border-green-200 dark:bg-green-900/20 dark:border-green-800">
                <span class="flex h-3 w-3 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
                <p class="text-lg font-medium text-green-700 dark:text-green-300">Connected</p>
            </div>
            
            {{-- Tenant Name --}}
            <p class="text-gray-600 dark:text-gray-300 mb-2 font-semibold">Connected Organization:</p>
            <div class="p-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-mono break-all mb-4 text-black dark:text-white">
                {{ $xeroStatus['tenant_name'] ?? 'N/A' }}
            </div>
            <!--
            {{-- Tenant ID --}}
            <p class="text-gray-600 dark:text-gray-300 mb-2 font-semibold">Organization ID (Tenant ID):</p>
            <div class="p-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-mono break-all mb-4 text-black dark:text-white">
                {{ $xeroStatus['tenant_id'] ?? 'N/A' }}
            </div>
        -->
            
            {{-- Token Expiry --}}
            <p class="text-gray-600 dark:text-gray-300 mb-2 font-semibold">Access Token Expiry:</p>
            @php
                $needsRefresh = $xeroStatus['needs_refresh'] ?? false;
            @endphp
            <div class="p-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-mono break-all mb-4 text-black dark:text-white @if($needsRefresh) text-red-500 font-bold @endif">
                {{ $xeroStatus['expires_at'] ?? 'Unknown' }}
            </div>
            
            {{-- Action Buttons --}}
            <div class="flex justify-between items-center">
                
                {{-- Disconnect Button (Livewire action) --}}
                <button
                    wire:click="disconnectXero"
                    class="px-6 py-3 bg-gray-500 text-white font-semibold rounded-lg shadow-md hover:bg-gray-600 transition duration-150"
                >
                    Disconnect
                </button>
            </div>

        {{--------------------------------------------------}}
        {{-- Disconnected State (ELSE) --}}
        {{--------------------------------------------------}}
        @else
            
            {{-- Connection Status Tag --}}
            <div class="flex items-center space-x-3 mb-6 p-3 bg-red-50 rounded-lg border border-red-200 dark:bg-red-900/20 dark:border-red-800">
                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                <p class="text-lg font-medium text-red-700 dark:text-red-300">Disconnected</p>
            </div>
            
            {{-- Description --}}
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Your account is not currently connected to a Xero organization. Please click the button below to authorize access.
            </p>
            
            {{-- Connect Button (Standard link/route) --}}
            <a href="{{ route('xero.authorize') }}" class="w-full block text-center">
                <button class="w-full px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition duration-150 disabled:opacity-50">
                    Connect to Xero
                </button>
            </a>
            
        @endif
</div>