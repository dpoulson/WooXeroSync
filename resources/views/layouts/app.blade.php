<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles

        <style>
            /* This is where the Xero App Launcher button usually injects itself. */
            /* It often appears in the bottom right corner. */
            .app-content { min-height: 80vh; padding: 20px; }
        </style>  
    </head>
    <body class="font-sans antialiased">


        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @livewire('navigation-menu')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif
            <x-banner />
            <!-- Page Content -->
            <main>
                <div class="py-4 sm:px-6 lg:px-8"> 
                    {{ $slot }}
                </div>
            </main>
        </div>
        <div id="xero-app-launcher-placeholder">
            <!-- The launcher might use this placeholder, but often injects itself into the body -->
        </div>
        @stack('modals')

        @livewireScripts

        <script src="https://app-cdn.xero.com/assets/xero-app-launcher.js"></script>
        <script>
            // The script checks for the window.XeroAppLauncher object.
            if (window.XeroAppLauncher) {
                
                // --- CRITICAL CONFIGURATION ---
                const appId = {{ config('services.xero.client_id') }};
                const serviceUrl = "{{ url('/') }}"; // The base URL of your application
                
                window.XeroAppLauncher.init({
                    appId: appId,
                    serviceUrl: serviceUrl
                });
                
                const tenantId = '{{ Auth::user()->currentTeam->xeroConnection?->tenant_id }}';
                const tenantName = '{{ Auth::user()->currentTeam->xeroConnection?->tenant_name }}';

                if (tenantId && tenantName) {
                    
                    // Call the SDK method to publish the tenant details
                    window.XeroAppLauncher.setTenantInfo({
                        tenantId: tenantData.tenantId,
                        tenantName: tenantData.tenantName
                    });

                    console.log("Xero Tenant Info set for:", tenantName);
                } else {
                    console.log("Xero App Launcher initialized, but no tenant information available (user likely disconnected).");
                }
    
            } else {
                console.error("Xero App Launcher script failed to load.");
            }
        </script>
    </body>
</html>
