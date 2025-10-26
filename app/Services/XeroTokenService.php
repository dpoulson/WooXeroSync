<?php

namespace App\Services;

use App\Models\User; // Assuming your User model is in this namespace
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Carbon\Carbon; 
use Exception;

/**
 * Handles all token-related operations, primarily refreshing expired access tokens.
 */
class XeroTokenService
{
    private const TOKEN_URL = 'https://identity.xero.com/connect/token';
    private const REVOCATION_URL = 'https://identity.xero.com/connect/revocation'; // New revocation endpoint

    /**
     * Executes an authenticated Xero API call, ensuring the token is valid first.
     *
     * @param User $user
     * @param string $method HTTP method (GET, POST)
     * @param string $endpoint The API endpoint (e.g., 'Contacts' or 'Invoices')
     * @param array|null $data Payload for POST/PUT requests
     * @return array The JSON response body
     * @throws Exception
     */
    public static function makeApiCall(User $user, string $method, string $endpoint, ?array $data = null): array
    {
        $accessToken = self::getValidAccessToken($user);
        $tenantId = Crypt::decryptString($user->xero_tenant_id);
        
        if (!$tenantId) {
            throw new Exception("Xero Tenant ID is missing. Please reconnect to Xero.");
        }

        $url = "https://api.xero.com/api.xro/2.0/{$endpoint}";

        $request = Http::withToken($accessToken)
            ->withHeaders(['Xero-Tenant-Id' => $tenantId])
            ->timeout(30)
            ->withHeaders(['Accept' => 'application/json']);

        $payload = $data;
        if (strtoupper($method) === 'POST' || strtoupper($method) === 'PUT') {
            $payload = self::wrapPayload($endpoint, $data);
        }        

        $response = match (strtoupper($method)) {
            'GET' => $request->get($url, $data),
            'POST' => $request->post($url, $payload),
            default => throw new Exception("Unsupported HTTP method: {$method}"),
        };

        if ($response->failed()) {
            $errorBody = $response->json();
            $errorMessage = $errorBody['Message'] ?? 'An unknown Xero API error occurred.';
            Log::error("Xero API Call Failed ({$method} {$endpoint}): {$errorMessage}", [
                'user_id' => $user->id,
                'status' => $response->status(),
                'response' => $errorBody,
                'payload' => $data
            ]);
            
            // Check for potential token expiration error specific to the API call
            if ($response->status() === 401 && (Str::contains($errorMessage, 'expired') || Str::contains($errorMessage, 'Invalid') )) {
                throw new Exception("Xero API returned 401 Unauthorized. The access token might be invalid, please try disconnecting and reconnecting.");
           }
            
            throw new Exception("Xero API Error: [HTTP {$response->status()}] {$errorMessage}");
        }
        
        // For POST requests, Xero returns the created objects within a wrapping array (e.g., ['Invoices' => [...] ])
        $responseData = $response->json();
        return $responseData;
    }    

    /**
     * Helper to wrap the data array with the correct Xero top-level key (e.g., 'Contacts' or 'Invoices').
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    private static function wrapPayload(string $endpoint, array $data): array
    {
        // Xero endpoints are case-sensitive. We get the base name from the path.
        $pathSegments = explode('/', $endpoint);
        $endpointBase = end($pathSegments);

        // Explicitly handle exceptions where Str::singular might be ambiguous or Xero expects specific casing/plurality.
        if ($endpointBase === 'Payments') {
            $key = 'Payments'; // Use the plural wrapper for Payments endpoint
        } elseif (Str::endsWith($endpointBase, 's')) {
            // General rule: Use the plural endpoint name as the key
            $key = $endpointBase;
        } else {
            // Fallback: This shouldn't be hit for common endpoints
            $key = $endpointBase;
        }

        return [$key => $data];
    }

    /**
     * Attempts to revoke the refresh token with Xero and clears all stored data.
     *
     * @param User $user
     * @return void
     */
    public static function revokeConnection(User $user): void
    {
        $refreshToken = Crypt::decryptString($user->xero_refresh_token);

        // 1. Attempt API Revocation
        if (!empty($refreshToken)) {
            try {
                // Xero expects the client ID and the token to be revoked (we use refresh token)
                $response = Http::asForm()->post(self::REVOCATION_URL, [
                    'client_id' => config('services.xero.client_id'),
                    'token' => $refreshToken,
                    'token_type_hint' => 'refresh_token',
                ]);

                if ($response->successful()) {
                    Log::info("Xero refresh token successfully revoked for user {$user->id}.");
                } else {
                    // Log the failure but continue to local cleanup, as the tokens are now unusable anyway
                    Log::warning('Xero API token revocation failed, but proceeding with local cleanup.', [
                        'user_id' => $user->id, 
                        'status' => $response->status(), 
                        'body' => $response->body()
                    ]);
                }
            } catch (Exception $e) {
                // Log and ignore network errors, focus on local cleanup
                Log::error('Network error during Xero token revocation.', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        // 2. Local Database Cleanup (Crucial step regardless of API success)
        $user->xero_access_token = null;
        $user->xero_refresh_token = null;
        $user->xero_token_expires_at = null;
        $user->xero_tenant_id = null;
        $user->xero_tenant_name = null;
        $user->save();
    }

    /**
     * Determines the current connection status for the user.
     *
     * @param User $user
     * @return array
     */
    public static function getConnectionStatus(User $user): array
    {
        $isConnected = !empty($user->xero_access_token) && !empty($user->xero_tenant_id);

        $expiresAt = $user->xero_token_expires_at;
        if (is_string($expiresAt) && !empty($expiresAt)) {
            try {
                $expiresAt = Carbon::parse($expiresAt);
            } catch (\Exception $e) {
                // Should not happen if data is correctly saved, but good for robustness
                Log::warning('Could not parse xero_token_expires_at string: ' . $user->xero_token_expires_at);
                $expiresAt = null; 
            }
        }

        $status = [
            'connected' => $isConnected,
            'tenant_id' => !empty($user->xero_tenant_id) ? Crypt::decryptString($user->xero_tenant_id) : null,
            'tenant_name' => $user->xero_tenant_name,
            'expires_at' => $expiresAt ? $expiresAt->format('Y-m-d H:i:s T') : null,
            'needs_refresh' => false,
            'message' => 'Not connected to Xero.',
        ];

        if ($isConnected) {
            // Check if token is nearing expiry (using the same 60-second buffer as the refresh logic)
            if (now()->addSeconds(60)->greaterThan($user->xero_token_expires_at)) {
                $status['needs_refresh'] = true;
                $status['message'] = 'Connected, but token is expired or requires immediate refresh.';
            } else {
                $status['message'] = 'Successfully connected and tokens are valid.';
            }
        }

        return $status;
    }

    /**
     * Checks if the token is expired and refreshes it if necessary.
     * This is the entry point for ensuring a token is valid before an API call.
     *
     * @param User $user The authenticated user model instance.
     * @return string The valid (or newly refreshed) access token.
     * @throws Exception if token refreshing fails.
     */
    public static function getValidAccessToken(User $user): string
    {
        // 1. Check if the token needs refreshing (e.g., expires within the next 60 seconds)
        // Add a 60-second buffer to prevent token expiry during an ongoing request.
        if (now()->addSeconds(60)->greaterThan($user->xero_token_expires_at)) {
            Log::info("Xero token for user {$user->id} expired. Attempting refresh...");
            return self::refreshAccessToken($user);
        }

        // Token is still valid
        return !empty($user->xero_access_token) ? Crypt::decryptString($user->xero_access_token) : null;
    }

    /**
     * Executes the OAuth 2.0 Refresh Token grant flow.
     *
     * @param User $user
     * @return string The new access token.
     * @throws Exception
     */
    public static function refreshAccessToken(User $user): string
    {
        if (empty($user->xero_refresh_token)) {
            throw new Exception("Cannot refresh token: Refresh token missing for user {$user->id}.");
        }

        try {
            // PKCE Refresh Grant only requires client_id, refresh_token, and grant_type.
            $response = Http::asForm()->post(self::TOKEN_URL, [
                'grant_type' => 'refresh_token',
                'client_id' => config('services.xero.client_id'), // Client ID required even for PKCE refresh
                'refresh_token' => Crypt::decryptString($user->xero_refresh_token),
            ]);

            if ($response->failed()) {
                Log::error('Xero Token Refresh Failed.', [
                    'user_id' => $user->id,
                    'response' => $response->body()
                ]);

                // Xero often returns 400 for an expired refresh token, requiring re-authorization
                if ($response->status() === 400) {
                    throw new Exception("Refresh token is invalid or expired. User must re-authorize Xero.");
                }

                throw new Exception('Failed to refresh Xero access token.');
            }

            $data = $response->json();

            self::saveConnectionData($user, $data, $user->xero_tenant_id, $user->xero_tenant_name);

            Log::info("Xero token for user {$user->id} successfully refreshed.");

            return $user->xero_access_token;

        } catch (Exception $e) {
            // Re-throw the exception for the calling function to handle
            throw $e;
        }
    }

    /**
     * Helper to save connection details to the user model.
     * Moved from controller to service for centralization.
     */
    public static function saveConnectionData($user, array $tokenData, string $tenantId = null, string $tenantName = null): void
    {
        $user->xero_access_token = Crypt::encryptString($tokenData['access_token']);
        // Use new refresh token if provided (it almost always is in the refresh grant response)
        $user->xero_refresh_token = Crypt::encryptString($tokenData['refresh_token']) ?? Crypt::encryptString($user->xero_refresh_token); 
        $user->xero_token_expires_at = now()->addSeconds($tokenData['expires_in']);
        if ($tenantId) {
            $user->xero_tenant_id = Crypt::encryptString($tenantId);
        }
        if ($tenantName) { // NEW: Save tenant name
            $user->xero_tenant_name = $tenantName;
        }
        $user->save();
    }
}
