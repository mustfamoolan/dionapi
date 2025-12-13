<?php

namespace App\Services;

use App\Services\FirebaseService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NotificationService
{
    protected $firebaseService;
    protected $fcmUrl = 'https://fcm.googleapis.com/v1/projects/{project_id}/messages:send';

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
        $this->fcmUrl = str_replace('{project_id}', config('firebase.project_id'), $this->fcmUrl);
    }

    /**
     * Get FCM tokens for a user from Firestore
     */
    protected function getFCMTokens(string $firebaseUid): array
    {
        $client = $this->firebaseService->getClient($firebaseUid);
        
        if (!$client) {
            return [];
        }

        $tokens = [];

        // Check for fcm_token (single token)
        if (isset($client['fcm_token']) && !empty($client['fcm_token'])) {
            $tokens[] = $client['fcm_token'];
        }

        // Check for fcm_tokens (array of tokens)
        if (isset($client['fcm_tokens']) && is_array($client['fcm_tokens'])) {
            $tokens = array_merge($tokens, array_filter($client['fcm_tokens']));
        }

        // Remove duplicates
        return array_unique($tokens);
    }

    /**
     * Send FCM notification using REST API
     */
    protected function sendFCM(array $tokens, array $notification, array $data = []): array
    {
        if (empty($tokens)) {
            return [
                'success' => false,
                'message' => 'لا توجد tokens للإرسال',
                'sent' => 0,
                'failed' => 0,
            ];
        }

        $results = [
            'success' => true,
            'sent' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // Use multicast for multiple tokens (up to 500 per batch)
        $batches = array_chunk($tokens, 500);
        
        foreach ($batches as $batch) {
            if (count($batch) === 1) {
                // Single token - use send API
                $result = $this->sendSingleFCM($batch[0], $notification, $data);
            } else {
                // Multiple tokens - use batch API
                $result = $this->sendBatchFCM($batch, $notification, $data);
            }

            $results['sent'] += $result['sent'] ?? 0;
            $results['failed'] += $result['failed'] ?? 0;
            
            if (isset($result['errors'])) {
                $results['errors'] = array_merge($results['errors'], $result['errors']);
            }
        }

        return $results;
    }

    /**
     * Send FCM to single token
     */
    protected function sendSingleFCM(string $token, array $notification, array $data = []): array
    {
        try {
            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $notification['title'] ?? '',
                        'body' => $notification['body'] ?? '',
                    ],
                    'data' => $this->formatFCMData(array_merge([
                        'type' => $data['type'] ?? 'general',
                        'timestamp' => now()->toIso8601String(),
                    ], $data)),
                    'android' => [
                        'priority' => 'high',
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                return ['sent' => 1, 'failed' => 0];
            } else {
                Log::error('FCM send failed', [
                    'token' => substr($token, 0, 20) . '...',
                    'response' => $response->body(),
                ]);
                return ['sent' => 0, 'failed' => 1, 'errors' => [$response->body()]];
            }
        } catch (\Exception $e) {
            Log::error('FCM send error: ' . $e->getMessage());
            return ['sent' => 0, 'failed' => 1, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Send FCM to multiple tokens (batch)
     */
    protected function sendBatchFCM(array $tokens, array $notification, array $data = []): array
    {
        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($tokens as $token) {
            $result = $this->sendSingleFCM($token, $notification, $data);
            $sent += $result['sent'];
            $failed += $result['failed'];
            
            if (isset($result['errors'])) {
                $errors = array_merge($errors, $result['errors']);
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Get OAuth2 access token for FCM
     */
    protected function getAccessToken(): string
    {
        // Use the same method as FirebaseService
        return Cache::remember('fcm_access_token', 50 * 60, function () {
            $credentialsPath = config('firebase.credentials');
            $credentials = json_decode(file_get_contents($credentialsPath), true);
            
            $jwt = $this->createJWT($credentials);
            
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (!$response->successful()) {
                Log::error('Failed to get FCM access token', [
                    'response' => $response->body(),
                ]);
                throw new \Exception('Failed to get FCM access token');
            }

            return $response->json()['access_token'];
        });
    }

    /**
     * Create JWT for service account authentication
     */
    protected function createJWT(array $credentials): string
    {
        $now = time();
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ];

        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signature = '';
        $privateKey = openssl_pkey_get_private($credentials['private_key']);
        if (!$privateKey) {
            throw new \Exception('Failed to load private key');
        }
        
        openssl_sign(
            $base64UrlHeader . '.' . $base64UrlPayload,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );
        
        openssl_free_key($privateKey);

        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }

    /**
     * Base64 URL encode
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Format FCM data - all values must be strings
     */
    protected function formatFCMData(array $data): array
    {
        $formatted = [];
        foreach ($data as $key => $value) {
            // FCM data values must be strings
            if (is_array($value)) {
                $formatted[$key] = json_encode($value);
            } else {
                $formatted[$key] = (string) $value;
            }
        }
        return $formatted;
    }

    /**
     * Send notification to a single user
     */
    public function sendToUser(string $firebaseUid, array $notification, array $data = []): array
    {
        $tokens = $this->getFCMTokens($firebaseUid);
        
        if (empty($tokens)) {
            return [
                'success' => false,
                'message' => 'المستخدم لا يملك FCM tokens',
                'sent' => 0,
                'failed' => 0,
            ];
        }

        return $this->sendFCM($tokens, $notification, $data);
    }

    /**
     * Send notification to multiple users
     */
    public function sendToMultiple(array $firebaseUids, array $notification, array $data = []): array
    {
        $allTokens = [];

        foreach ($firebaseUids as $firebaseUid) {
            $tokens = $this->getFCMTokens($firebaseUid);
            $allTokens = array_merge($allTokens, $tokens);
        }

        if (empty($allTokens)) {
            return [
                'success' => false,
                'message' => 'لا توجد FCM tokens للمستخدمين المحددين',
                'sent' => 0,
                'failed' => 0,
            ];
        }

        return $this->sendFCM($allTokens, $notification, $data);
    }

    /**
     * Send notification to all users with optional filter
     */
    public function sendToAll(array $notification, array $data = [], array $filter = []): array
    {
        // Use getClientsByFilter if filter is provided, otherwise getAllClients
        $clients = !empty($filter) 
            ? $this->firebaseService->getClientsByFilter($filter)
            : $this->firebaseService->getAllClients();
        
        $allTokens = [];

        foreach ($clients as $client) {
            $tokens = $this->getFCMTokens($client['firebase_uid']);
            $allTokens = array_merge($allTokens, $tokens);
        }

        if (empty($allTokens)) {
            return [
                'success' => false,
                'message' => 'لا توجد FCM tokens للمستخدمين',
                'sent' => 0,
                'failed' => 0,
            ];
        }

        return $this->sendFCM($allTokens, $notification, $data);
    }
}

