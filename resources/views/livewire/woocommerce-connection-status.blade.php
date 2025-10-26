<div class="bg-gray-50 p-6 rounded-xl border border-gray-200 shadow-inner dark:bg-gray-700 dark:border-gray-600">
    <div class="flex items-center justify-between mb-4">
    <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">WooCommerce Connection</h2>
    <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
    </svg>
    </div>
    
    @if ($wcStatus['connected'])
        
        <!-- Status: CONNECTED -->
        <div class="flex items-center space-x-3 mb-6 p-3 bg-green-50 rounded-lg border border-green-200 dark:bg-green-900/20 dark:border-green-800">
            <span class="flex h-3 w-3 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
            </span>
            <p class="text-lg font-medium text-green-700 dark:text-green-300">Connected</p>
        </div>
    
        <p class="text-gray-600 dark:text-gray-300 mb-2 font-semibold">Store URL:</p>
        <div class="p-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-mono break-all mb-4 text-black dark:text-white">
            {{ $wcStatus['url'] }}
        </div>
        
        <p class="text-gray-600 dark:text-gray-300 mb-2">Credentials Status:</p>
        <div class="p-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-mono break-all mb-6 text-black dark:text-white">
            {{ $wcStatus['key_status'] }} (Stored Securely)
        </div>
    
        <button wire:click="toggleConfigForm" class="w-full px-6 py-3 bg-indigo-500 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-600 transition duration-150">
            @if($showConfigForm) Hide Configuration @else Reconfigure / Update Credentials @endif
        </button>
        
    @else
        <!-- Status: DISCONNECTED -->
        <div class="flex items-center space-x-3 mb-6 p-3 bg-red-50 rounded-lg border border-red-200 dark:bg-red-900/20 dark:border-red-800">
            <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
            <p class="text-lg font-medium text-red-700 dark:text-red-300">Disconnected</p>
        </div>
        
        <p class="text-gray-600 dark:text-gray-400 mb-2">Connect your WooCommerce store using a REST API key and secret.</p>
    @endif
    
    <!-- Configuration Form (NOW ALWAYS controlled by $showConfigForm) -->
    <div x-cloak 
         x-show="$wire.showConfigForm" 
         class="mt-6 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Enter your WooCommerce Store URL and REST API Key/Secret below.</p>
        
        <form wire:submit.prevent="saveConnection">
            
            <div class="mb-4">
                <label for="woocommerce_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Store URL</label>
                <input type="url" id="woocommerce_url" required
                    wire:model="woocommerce_url"
                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2 border text-black dark:text-white"
                    placeholder="https://yourstore.com">
                @error('woocommerce_url') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
    
            <div class="mb-4">
                <label for="woocommerce_consumer_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Consumer Key</label>
                <input type="text" id="woocommerce_consumer_key" required
                    wire:model="woocommerce_consumer_key"
                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2 border text-black dark:text-white"
                    placeholder="ck_xxxxxxxxxxxxxxxxx">
                @error('woocommerce_consumer_key') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
    
            <div class="mb-6">
                <label for="woocommerce_consumer_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Consumer Secret</label>
                <input type="text" id="woocommerce_consumer_secret" required
                    wire:model="woocommerce_consumer_secret"
                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2 border text-black dark:text-white"
                    placeholder="cs_xxxxxxxxxxxxxxxxx">
                @error('woocommerce_consumer_secret') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
    
            <button type="submit" 
                    wire:loading.attr="disabled"
                    class="w-full px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition duration-150 disabled:opacity-50">
                <span wire:loading.remove wire:target="saveConnection">Save & Test Connection</span>
                <span wire:loading wire:target="saveConnection">Connecting...</span>
            </button>
        </form>
    </div>
    
    
    </div>