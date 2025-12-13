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
     * Uses FirebaseService's getClientFCMTokens() method directly for better efficiency
     */
    protected function getFCMTokens(string $firebaseUid): array
    {
        return $this->firebaseService->getClientFCMTokens($firebaseUid);
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
        $usersWithTokens = [];
        $usersWithoutTokens = [];

        foreach ($firebaseUids as $firebaseUid) {
            $tokens = $this->getFCMTokens($firebaseUid);
            if (!empty($tokens)) {
                $allTokens = array_merge($allTokens, $tokens);
                $usersWithTokens[] = $firebaseUid;
            } else {
                $usersWithoutTokens[] = $firebaseUid;
                Log::info('User has no FCM tokens', ['firebase_uid' => $firebaseUid]);
            }
        }

        if (empty($allTokens)) {
            return [
                'success' => false,
                'message' => 'لا توجد FCM tokens لأي من المستخدمين المحددين',
                'sent' => 0,
                'failed' => 0,
                'users_with_tokens' => 0,
                'users_without_tokens' => count($usersWithoutTokens),
            ];
        }

        $result = $this->sendFCM($allTokens, $notification, $data);
        
        // Add additional info
        $result['users_with_tokens'] = count($usersWithTokens);
        $result['users_without_tokens'] = count($usersWithoutTokens);
        
        if (count($usersWithoutTokens) > 0) {
            $result['message'] = sprintf(
                'تم إرسال الإشعار لـ %d مستخدم (فشل %d مستخدم ليس لديهم tokens)',
                count($usersWithTokens),
                count($usersWithoutTokens)
            );
        }

        return $result;
    }

    /**
     * Send notification to all users with optional filter
     */
    public function sendToAll(array $notification, array $data = [], array $filter = []): array
    {
        try {
            Log::info('Starting sendToAll', [
                'filter' => $filter,
                'notification_title' => $notification['title'] ?? '',
            ]);

            // Use getClientsByFilter if filter is provided, otherwise getAllClients
            $clients = !empty($filter) 
                ? $this->firebaseService->getClientsByFilter($filter)
                : $this->firebaseService->getAllClients();
            
            Log::info('Fetched clients for sendToAll', [
                'total_clients' => count($clients),
                'has_filter' => !empty($filter),
            ]);

            if (empty($clients)) {
                Log::warning('No clients found for sendToAll', ['filter' => $filter]);
                return [
                    'success' => false,
                    'message' => 'لا توجد مستخدمين للرسالة',
                    'sent' => 0,
                    'failed' => 0,
                    'users_with_tokens' => 0,
                    'users_without_tokens' => 0,
                    'total_users' => 0,
                ];
            }
            
            $allTokens = [];
            $usersWithTokens = [];
            $usersWithoutTokens = [];

            foreach ($clients as $client) {
                $firebaseUid = $client['firebase_uid'] ?? null;
                if (!$firebaseUid) {
                    Log::warning('Client missing firebase_uid', ['client' => $client]);
                    continue;
                }
                
                try {
                    $tokens = $this->getFCMTokens($firebaseUid);
                    if (!empty($tokens)) {
                        $allTokens = array_merge($allTokens, $tokens);
                        $usersWithTokens[] = $firebaseUid;
                        Log::debug('User has FCM tokens', [
                            'firebase_uid' => $firebaseUid,
                            'tokens_count' => count($tokens),
                        ]);
                    } else {
                        $usersWithoutTokens[] = $firebaseUid;
                        Log::debug('User has no FCM tokens', ['firebase_uid' => $firebaseUid]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error getting FCM tokens for user', [
                        'firebase_uid' => $firebaseUid,
                        'error' => $e->getMessage(),
                    ]);
                    $usersWithoutTokens[] = $firebaseUid;
                }
            }

            Log::info('Collected tokens for sendToAll', [
                'users_with_tokens' => count($usersWithTokens),
                'users_without_tokens' => count($usersWithoutTokens),
                'total_tokens' => count($allTokens),
            ]);

            if (empty($allTokens)) {
                Log::warning('No FCM tokens found for any user', [
                    'total_users' => count($clients),
                    'users_without_tokens' => count($usersWithoutTokens),
                ]);
                return [
                    'success' => false,
                    'message' => 'لا توجد FCM tokens لأي من المستخدمين',
                    'sent' => 0,
                    'failed' => 0,
                    'users_with_tokens' => 0,
                    'users_without_tokens' => count($usersWithoutTokens),
                    'total_users' => count($clients),
                ];
            }

            Log::info('Sending FCM notifications', [
                'total_tokens' => count($allTokens),
                'notification_title' => $notification['title'] ?? '',
            ]);

            $result = $this->sendFCM($allTokens, $notification, $data);
            
            // Add additional info
            $result['users_with_tokens'] = count($usersWithTokens);
            $result['users_without_tokens'] = count($usersWithoutTokens);
            $result['total_users'] = count($clients);
            
            if (count($usersWithoutTokens) > 0) {
                $result['message'] = sprintf(
                    'تم إرسال الإشعار لـ %d مستخدم من أصل %d (فشل %d مستخدم ليس لديهم tokens)',
                    count($usersWithTokens),
                    count($clients),
                    count($usersWithoutTokens)
                );
            } else {
                $result['message'] = sprintf(
                    'تم إرسال الإشعار لجميع المستخدمين (%d مستخدم)',
                    count($usersWithTokens)
                );
            }

            Log::info('Completed sendToAll', [
                'sent' => $result['sent'] ?? 0,
                'failed' => $result['failed'] ?? 0,
                'users_with_tokens' => count($usersWithTokens),
                'users_without_tokens' => count($usersWithoutTokens),
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Error in sendToAll', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الإشعارات: ' . $e->getMessage(),
                'sent' => 0,
                'failed' => 0,
                'users_with_tokens' => 0,
                'users_without_tokens' => 0,
                'total_users' => 0,
            ];
        }
    }
}

