<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth; // Assuming standard Laravel Auth is used
use Illuminate\Support\Facades\Log;
use App\Services\XeroTokenService;

/**
 * Handles the Xero OAuth 2.0 authorization flow.
 *
 * NOTE: This requires the Guzzle/Laravel HTTP client and proper .env configuration.
 */
class XeroAuthController extends Controller
{
    // --- Configuration Constants (Read from .env) ---
    private const AUTH_URL = 'https://login.xero.com/identity/connect/authorize';
    private const TOKEN_URL = 'https://identity.xero.com/connect/token';
    private const CONNECTIONS_URL = 'https://api.xero.com/connections';

    private function base64UrlEncode(string $data): string
    {
        // Base64 encode and then replace + and / with - and _ respectively, and remove padding =
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Generates a secure random code verifier (32 bytes, base64url-encoded).
     * @return string
     */
    private function generateCodeVerifier(): string
    {
        return $this->base64UrlEncode(random_bytes(32));
    }

    /**
     * Generates the code challenge (SHA256 hash of verifier, base64url-encoded).
     * @param string $verifier
     * @return string
     */
    private function generateCodeChallenge(string $verifier): string
    {
        $sha256 = hash('sha256', $verifier, true);
        return $this->base64UrlEncode($sha256);
    }

    /**
     * Step 1: Redirects the user to the Xero authorization screen.
     */
    public function authorizeXero(Request $request)
    {

        $codeVerifier = $this->generateCodeVerifier();
        $codeChallenge = $this->generateCodeChallenge($codeVerifier);

        // Define the scopes required:
        // 'accounting.transactions' for invoices, 'offline_access' for a refresh token.
        $scopes = [
            'openid', 
            'profile', 
            'email', 
            // WRITE scopes for creating Contacts, Invoices, Payments
            'accounting.transactions', 
            'accounting.contacts', 
            // READ scopes for checking existing invoices and reading Chart of Accounts (MANDATORY for Accounts list)
            'accounting.transactions.read', 
            'accounting.contacts.read',
            'accounting.settings',
            // Token refresh scope
            'offline_access'
        ];

        // Generate a random state string for CSRF protection and verification later.
        $state = bin2hex(random_bytes(16));

        // In a real app, you would store this $state in the session or database,
        // linked to the current user, to verify it in the callback method.
        session(['xero_oauth_state' => $state, 'xero_code_verifier' => $codeVerifier]);

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.xero.client_id'),
            'redirect_uri' => config('services.xero.redirect'),
            'scope' => implode(' ', $scopes),
            'state' => $state,
            'code_challenge' => $codeChallenge, // PKCE required parameter
            'code_challenge_method' => 'S256',  // PKCE required parameter
        ]);
        return redirect(self::AUTH_URL . '?' . $query);
    }

    /**
     * Step 2 & 3: Handles the callback from Xero, exchanges the code for tokens.
     */
    public function handleCallback(Request $request)
    {
        // Check for error response from Xero
        if ($request->has('error')) {
            Log::error('Xero Authorization Error: ' . $request->get('error') . ' - ' . $request->get('error_description'));
            return redirect('/dashboard')->with('error', 'Xero authorization failed: ' . $request->get('error_description'));
        }

        // --- 1. State Validation (Security Check) ---
        $expectedState = session('xero_oauth_state');
        $codeVerifier = session('xero_code_verifier');
        session()->forget(['xero_oauth_state', 'xero_code_verifier']); // Clean up session

        if (!$request->has('state') || $request->get('state') !== $expectedState) {
            Log::warning('Xero OAuth State Mismatch/Missing: Potential CSRF attack.');
            return redirect('/dashboard')->with('error', 'Authorization state validation failed. Please try again.');
        }

        if (empty($codeVerifier)) {
            Log::warning('Xero OAuth Code Verifier Missing: Cannot complete PKCE flow.');
            return redirect('/dashboard')->with('error', 'Authorization configuration error. Code Verifier missing.');
        }

        $code = $request->get('code');

        // --- 2. Token Exchange (using code_verifier instead of client_secret) ---
        try {
            $response = Http::asForm()->post(self::TOKEN_URL, [
                'grant_type' => 'authorization_code',
                'client_id' => config('services.xero.client_id'),
                'redirect_uri' => config('services.xero.redirect'),
                'code' => $code,
                'code_verifier' => $codeVerifier, // PKCE required parameter
            ]);

            if ($response->failed()) {
                Log::error('Xero Token Exchange Failed: ' . $response->body());
                return redirect('/dashboard')->with('error', 'Failed to exchange authorization code for tokens.');
            }

            $data = $response->json();
            $accessToken = $data['access_token'];
            $currentTeam = auth()->user()->currentTeam;

            if (!$currentTeam) {
                return redirect('/login')->with('error', 'User session lost during connection.');
            }

            // --- 2. Retrieve Tenant ID (Organization ID) ---
            $connectionResponse = Http::withToken($accessToken)
                ->get(self::CONNECTIONS_URL);

            if ($connectionResponse->failed()) {
                Log::error('Xero Connection Retrieval Failed.', ['user_id' => $user->id, 'response' => $connectionResponse->body()]);
                throw new Exception('Successfully authorized, but failed to retrieve Xero organization details (Tenant ID and Name).');
            }

            $connections = $connectionResponse->json();
            // --- 3. Store Tokens and Tenant ID ---
            // Assuming the user only connects one organization
            $tenantId = $connections[0]['tenantId'];
            $tenantName = $connections[0]['tenantName'] ?? 'Unnamed Organisation';

            XeroTokenService::saveConnectionData($currentTeam, $data, $tenantId, $tenantName);

            return redirect('/dashboard')->with('success', "Successfully connected to Xero organization: {$tenantName}! You can now start syncing.");

        } catch (\Exception $e) {
            Log::error('Xero Token/Tenant Retrieval Exception: ' . $e->getMessage());
            return redirect('/dashboard')->with('error', 'An unexpected error occurred during Xero connection.');
        }
    }

    /**
     * Handles the disconnection request, revokes tokens, and cleans up the database.
     */
    public function handleDisconnect(Request $request)
    {
        $currentTeam = auth()->user()->currentTeam;
        if (empty($currentTeam->XeroConnection->refresh_token)) {
            return redirect()->route('dashboard')->with('warning', 'You were already disconnected from Xero.');
        }

        try {
            XeroTokenService::revokeConnection($currentTeam);
            return redirect()->route('dashboard')->with('success', 'Successfully disconnected from Xero and revoked your tokens.');
        } catch (Exception $e) {
            return redirect()->route('dashboard')->with('error', 'An error occurred during disconnection: ' . $e->getMessage());
        }
    }
}
