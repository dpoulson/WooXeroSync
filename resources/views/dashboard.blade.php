<x-layouts.app :title="__('Dashboard')">

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    
        <div class="grid auto-rows-min gap-4 md:grid-cols-2"> 
            
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700">
                
                <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 shadow-inner">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-2xl font-semibold text-gray-700">Xero Connection</h2>
                        <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    
                    @if ($xeroStatus && $xeroStatus['connected'])
                        
                        <div class="flex items-center space-x-3 mb-4 p-3 bg-green-50 rounded-lg border border-green-200">
                            <span class="flex h-3 w-3 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                            <p class="text-lg font-medium text-green-700">Connected</p>
                        </div>
                        <p class="text-gray-600 mb-2 font-semibold">Connected Organization:</p>
                        <!-- NEW: Display Tenant Name -->
                        <div class="p-3 bg-black border border-gray-300 rounded-lg text-sm font-bold break-all mb-4 text-xero-secondary">
                            {{ $xeroStatus['tenant_name'] }}
                        </div>
                        <p class="text-gray-600 mb-2">Organization ID (Tenant ID):</p>
                        <div class="p-3 bg-black border border-gray-300 rounded-lg text-xs font-mono break-all mb-4">
                            {{ $xeroStatus['tenant_id'] }}
                        </div>
                        
                        <p class="text-gray-600 mb-2">Access Token Expiry:</p>
                        <div class="p-3 bg-black border border-gray-300 rounded-lg text-xs font-mono break-all mb-6 @if($xeroStatus['needs_refresh']) text-red-500 font-bold @endif">
                            {{ $xeroStatus['expires_at'] }}
                        </div>
            
                        <div class="flex justify-between items-center">
                            <!-- Updated Disconnect Link -->
                <a href="{{ route('xero.disconnect') }}" 
                onclick="event.preventDefault(); document.getElementById('disconnect-form').submit();"
                class="px-6 py-3 bg-gray-500 text-white font-semibold rounded-lg shadow-md hover:bg-gray-600 transition duration-150">
                 Disconnect
             </a>
             
             <!-- Hidden form to handle POST request for Disconnect -->
             <form id="disconnect-form" action="{{ route('xero.disconnect') }}" method="POST" style="display: none;">
                 @csrf
             </form>
             <button form="sync-form" type="submit" 
             class="px-6 py-3 bg-green text-white font-semibold rounded-lg shadow-md hover:opacity-90 transition duration-150
                     @if(!$wcStatus['connected']) opacity-50 cursor-not-allowed @endif"
             @if(!$wcStatus['connected']) disabled @endif>
         Run Initial Sync
     </button>
                        </div>
            
                    @else
                        
                        <div class="flex items-center space-x-3 mb-6 p-3 bg-red-50 rounded-lg border border-red-200">
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                            <p class="text-lg font-medium text-red-700">Disconnected</p>
                        </div>
                        
                        <p class="text-gray-600 mb-6">
                            Your account is not currently connected to a Xero organization. Please click the button below to authorize access.
                        </p>
            
                        <a href="{{ route('xero.authorize') }}" class="w-full block text-center">
                            <button class="w-full px-6 py-3 xero-secondary text-white font-bold rounded-lg shadow-xl hover:opacity-90 transition duration-150">
                                Connect to Xero
                            </button>
                        </a>
                    @endif
                </div>
            </div>
            

            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700">
                
                <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 shadow-inner">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-2xl font-semibold text-gray-700">WooCommerce Connection</h2>
                        <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </div>
        
                    @if ($wcStatus && $wcStatus['connected'])
                        
                        <!-- Status: CONNECTED -->
                        <div class="flex items-center space-x-3 mb-4 p-3 bg-green-50 rounded-lg border border-green-200">
                            <span class="flex h-3 w-3 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                            <p class="text-lg font-medium text-green-700">Connected</p>
                        </div>
        
                        <p class="text-gray-600 mb-2 font-semibold">Store URL:</p>
                        <div class="p-3 bg-black border border-gray-300 rounded-lg text-sm font-mono break-all mb-4">
                            {{ $wcStatus['url'] }}
                        </div>
                        
                        <p class="text-gray-600 mb-2">Credentials Status:</p>
                        <div class="p-3 bg-black border border-gray-300 rounded-lg text-sm font-mono break-all mb-6">
                            {{ $wcStatus['key_status'] }} (Stored Securely)
                        </div>
        
                        <button onclick="document.getElementById('wc-config-form-container').classList.toggle('hidden');" class="w-full px-6 py-3 bg-indigo-500 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-600 transition duration-150">
                            Reconfigure / Update Credentials
                        </button>
                        
                    @else
                        <!-- Status: DISCONNECTED -->
                        <div class="flex items-center space-x-3 mb-4 p-3 bg-red-50 rounded-lg border border-red-200">
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                            <p class="text-lg font-medium text-red-700">Disconnected</p>
                        </div>
        
                    @endif
        
                    <!-- Configuration Form (Visible if disconnected or if Reconfigure is clicked) -->
                    <div id="wc-config-form-container" class="@if($wcStatus['connected']) hidden @endif mt-6 p-4 bg-white rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-500 mb-4">Enter your WooCommerce Store URL and REST API Key/Secret below.</p>
                        
                        <form action="{{ route('wc.configure') }}" method="POST">
                            @csrf
                            
                            <div class="mb-4">
                                <label for="woocommerce_url" class="block text-sm font-medium text-gray-700">Store URL</label>
                                <input type="url" name="woocommerce_url" id="woocommerce_url" required
                                    class="mt-1 block w-full rounded-lg bg-black border-gray-300 shadow-sm focus:border-xero-secondary focus:ring-xero-secondary p-2 border"
                                    placeholder="https://yourstore.com" value="{{ old('woocommerce_url', $user->woocommerce_url) }}">
                            </div>
        
                            <div class="mb-4">
                                <label for="woocommerce_consumer_key" class="block text-sm font-medium text-gray-700">Consumer Key</label>
                                <input type="text" name="woocommerce_consumer_key" id="woocommerce_consumer_key" required
                                    class="mt-1 block w-full rounded-lg bg-black border-gray-300 shadow-sm focus:border-xero-secondary focus:ring-xero-secondary p-2 border"
                                    placeholder="ck_xxxxxxxxxxxxxxxxx">
                            </div>
        
                            <div class="mb-6">
                                <label for="woocommerce_consumer_secret" class="block text-sm font-medium text-gray-700">Consumer Secret</label>
                                <input type="text" name="woocommerce_consumer_secret" id="woocommerce_consumer_secret" required
                                    class="mt-1 block w-full rounded-lg bg-black border-gray-300 shadow-sm focus:border-xero-secondary focus:ring-xero-secondary p-2 border"
                                    placeholder="cs_xxxxxxxxxxxxxxxxx">
                            </div>
        
                            <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition duration-150">
                                Save & Test Connection
                            </button>
                        </form>
                    </div>
        
                </div>



            </div>
            
            </div>
        

            
            <!-- Hidden Form for Sync Button -->
            <form id="sync-form" action="{{ route('wc.sync') }}" method="POST" style="display: none;">
                @csrf
            </form>

    </div>
</x-layouts.app>
