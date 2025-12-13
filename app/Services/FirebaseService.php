<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FirebaseService
{
    protected $projectId;
    protected $credentials;
    protected $accessToken;
    protected $baseUrl;
    protected $usersCollection;
    protected $debtsCollection;
    protected $productsCollection;

    public function __construct()
    {
        $credentialsPath = config('firebase.credentials');
        $this->projectId = config('firebase.project_id');
        $this->usersCollection = config('firebase.collections.users');
        $this->debtsCollection = config('firebase.collections.debts', 'debts');
        $this->productsCollection = config('firebase.collections.products', 'products');
        $this->baseUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";

        // Load credentials
        if (!file_exists($credentialsPath)) {
            throw new \Exception("Firebase credentials file not found at: {$credentialsPath}");
        }

        $this->credentials = json_decode(file_get_contents($credentialsPath), true);

        // Get access token
        $this->accessToken = $this->getAccessToken();
    }

    /**
     * Get OAuth2 access token from service account
     */
    protected function getAccessToken(): string
    {
        // Cache token for 50 minutes (tokens expire after 1 hour)
        return Cache::remember('firebase_access_token', 50 * 60, function () {
            $jwt = $this->createJWT();

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (!$response->successful()) {
                Log::error('Failed to get Firebase access token', [
                    'response' => $response->body(),
                ]);
                throw new \Exception('Failed to get Firebase access token');
            }

            return $response->json()['access_token'];
        });
    }

    /**
     * Create JWT for service account authentication
     */
    protected function createJWT(): string
    {
        $now = time();
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'iss' => $this->credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/datastore',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ];

        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signature = '';
        $privateKey = openssl_pkey_get_private($this->credentials['private_key']);
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
     * Make authenticated request to Firestore REST API
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;

        $response = Http::withToken($this->accessToken)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->{strtolower($method)}($url, $data);

        if (!$response->successful()) {
            Log::error('Firestore API request failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            // If token expired, clear cache and retry once
            if ($response->status() === 401) {
                Cache::forget('firebase_access_token');
                $this->accessToken = $this->getAccessToken();

                $response = Http::withToken($this->accessToken)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                    ])
                    ->{strtolower($method)}($url, $data);
            }
        }

        return $response->json() ?? [];
    }

    /**
     * Convert Firestore value to PHP value
     */
    protected function fromFirestoreValue(array $value): mixed
    {
        if (isset($value['stringValue'])) {
            return $value['stringValue'];
        } elseif (isset($value['integerValue'])) {
            return (int) $value['integerValue'];
        } elseif (isset($value['doubleValue'])) {
            return (float) $value['doubleValue'];
        } elseif (isset($value['booleanValue'])) {
            return $value['booleanValue'];
        } elseif (isset($value['timestampValue'])) {
            return new \DateTime($value['timestampValue']);
        } elseif (isset($value['nullValue'])) {
            return null;
        } elseif (isset($value['mapValue']['fields'])) {
            $result = [];
            foreach ($value['mapValue']['fields'] as $key => $field) {
                $result[$key] = $this->fromFirestoreValue($field);
            }
            return $result;
        } elseif (isset($value['arrayValue']['values'])) {
            $result = [];
            foreach ($value['arrayValue']['values'] as $item) {
                $result[] = $this->fromFirestoreValue($item);
            }
            return $result;
        }

        return null;
    }

    /**
     * Convert PHP value to Firestore value
     */
    protected function toFirestoreValue($value): array
    {
        if ($value === null) {
            return ['nullValue' => null];
        } elseif (is_bool($value)) {
            return ['booleanValue' => $value];
        } elseif (is_int($value)) {
            return ['integerValue' => (string) $value];
        } elseif (is_float($value)) {
            return ['doubleValue' => $value];
        } elseif ($value instanceof \DateTime) {
            return ['timestampValue' => $value->format('c')];
        } elseif (is_array($value)) {
            if (isset($value[0])) {
                // Array
                $values = [];
                foreach ($value as $item) {
                    $values[] = $this->toFirestoreValue($item);
                }
                return ['arrayValue' => ['values' => $values]];
            } else {
                // Map
                $fields = [];
                foreach ($value as $key => $item) {
                    $fields[$key] = $this->toFirestoreValue($item);
                }
                return ['mapValue' => ['fields' => $fields]];
            }
        } else {
            return ['stringValue' => (string) $value];
        }
    }

    /**
     * Get all clients from Firestore
     */
    public function getAllClients(): array
    {
        try {
            $endpoint = "/{$this->usersCollection}";
            $response = $this->makeRequest('GET', $endpoint);

            $clients = [];

            if (isset($response['documents'])) {
                foreach ($response['documents'] as $document) {
                    $documentId = basename($document['name']);
                    $fields = [];

                    if (isset($document['fields'])) {
                        foreach ($document['fields'] as $key => $value) {
                            $fields[$key] = $this->fromFirestoreValue($value);
                        }
                    }

                    $fields['firebase_uid'] = $documentId;
                    $clients[] = $this->formatClientData($fields);
                }
            }

            // Sort by created_at descending
            usort($clients, function ($a, $b) {
                $aTime = $a['created_at'] ?? 0;
                $bTime = $b['created_at'] ?? 0;
                if ($aTime instanceof \DateTime) {
                    $aTime = $aTime->getTimestamp();
                }
                if ($bTime instanceof \DateTime) {
                    $bTime = $bTime->getTimestamp();
                }
                return $bTime <=> $aTime;
            });

            return $clients;
        } catch (\Exception $e) {
            Log::error('Error fetching clients from Firestore: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single client by Firebase UID
     */
    public function getClient(string $firebaseUid): ?array
    {
        try {
            $endpoint = "/{$this->usersCollection}/{$firebaseUid}";
            $response = $this->makeRequest('GET', $endpoint);

            if (!isset($response['fields'])) {
                return null;
            }

            $fields = [];
            foreach ($response['fields'] as $key => $value) {
                $fields[$key] = $this->fromFirestoreValue($value);
            }

            $fields['firebase_uid'] = $firebaseUid;

            return $this->formatClientData($fields);
        } catch (\Exception $e) {
            Log::error('Error fetching client from Firestore: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update client status
     */
    public function updateClientStatus(string $firebaseUid, string $status, ?int $months = null): bool
    {
        try {
            $now = new \DateTime();
            $updateData = [
                'status' => $status,
                'last_status_change_at' => $now,
                'updated_at' => $now,
            ];

            // If activating, set activation_expires_at
            if ($status === 'active' && $months !== null) {
                $expiresAt = new \DateTime();
                $expiresAt->modify("+{$months} months");
                $updateData['activation_expires_at'] = $expiresAt;
                $updateData['is_active'] = true;
            } elseif ($status === 'banned') {
                $updateData['is_active'] = false;
                $updateData['activation_expires_at'] = null;
            } elseif ($status === 'pending') {
                $updateData['is_active'] = false;
                $updateData['activation_expires_at'] = null;
            } elseif ($status === 'expired') {
                $updateData['is_active'] = false;
            }

            return $this->updateDocument($firebaseUid, $updateData);
        } catch (\Exception $e) {
            Log::error('Error updating client status in Firestore: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update document in Firestore
     */
    protected function updateDocument(string $firebaseUid, array $data): bool
    {
        try {
            $endpoint = "/{$this->usersCollection}/{$firebaseUid}";

            $fields = [];
            $updateMask = [];

            foreach ($data as $key => $value) {
                $updateMask[] = $key;
                if ($value !== null) {
                    $fields[$key] = $this->toFirestoreValue($value);
                } else {
                    $fields[$key] = ['nullValue' => null];
                }
            }

            // Build update mask query parameter
            $updateMaskParam = implode('&updateMask.fieldPaths=', array_map('urlencode', $updateMask));

            $payload = [
                'fields' => $fields,
            ];

            $url = $endpoint . '?updateMask.fieldPaths=' . $updateMaskParam;
            $this->makeRequest('PATCH', $url, $payload);

            return true;
        } catch (\Exception $e) {
            Log::error('Error updating document in Firestore: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update client activation expires date
     */
    public function updateClientActivationExpires(string $firebaseUid, ?\DateTime $expiresAt = null): bool
    {
        try {
            $updateData = [
                'updated_at' => new \DateTime(),
            ];

            if ($expiresAt !== null) {
                $updateData['activation_expires_at'] = $expiresAt;
            } else {
                $updateData['activation_expires_at'] = null;
            }

            return $this->updateDocument($firebaseUid, $updateData);
        } catch (\Exception $e) {
            Log::error('Error updating client activation expires in Firestore: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update client is_active
     */
    public function updateClientIsActive(string $firebaseUid, bool $isActive): bool
    {
        try {
            $now = new \DateTime();
            $updateData = [
                'is_active' => $isActive,
                'last_status_change_at' => $now,
                'updated_at' => $now,
            ];

            return $this->updateDocument($firebaseUid, $updateData);
        } catch (\Exception $e) {
            Log::error('Error updating client is_active in Firestore: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Format client data from Firestore
     */
    protected function formatClientData(array $data): array
    {
        return [
            'firebase_uid' => $data['firebase_uid'] ?? null,
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'governorate' => $data['governorate'] ?? null,
            'city' => $data['city'] ?? null,
            'photo_url' => $data['photo_url'] ?? null,
            'provider' => $data['provider'] ?? 'google',
            'provider_id' => $data['provider_id'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'is_active' => $data['is_active'] ?? false,
            'fcm_token' => $data['fcm_token'] ?? null,
            'fcm_tokens' => $data['fcm_tokens'] ?? null,
            'activation_expires_at' => $this->convertTimestamp($data['activation_expires_at'] ?? null),
            'last_status_change_at' => $this->convertTimestamp($data['last_status_change_at'] ?? null),
            'last_login_at' => $this->convertTimestamp($data['last_login_at'] ?? null),
            'created_at' => $this->convertTimestamp($data['created_at'] ?? null),
            'updated_at' => $this->convertTimestamp($data['updated_at'] ?? null),
        ];
    }

    /**
     * Get FCM tokens for a client
     */
    public function getClientFCMTokens(string $firebaseUid): array
    {
        $client = $this->getClient($firebaseUid);
        
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
     * Get clients by filter
     */
    public function getClientsByFilter(array $filter = []): array
    {
        $clients = $this->getAllClients();
        
        if (empty($filter)) {
            return $clients;
        }

        $filtered = [];

        foreach ($clients as $client) {
            $match = true;

            if (isset($filter['status']) && $client['status'] !== $filter['status']) {
                $match = false;
            }

            if (isset($filter['is_active']) && $client['is_active'] !== $filter['is_active']) {
                $match = false;
            }

            if ($match) {
                $filtered[] = $client;
            }
        }

        return $filtered;
    }

    /**
     * Update client data (general update method)
     */
    public function updateClientData(string $firebaseUid, array $data): bool
    {
        try {
            $updateData = [
                'updated_at' => new \DateTime(),
            ];

            // Check if status or is_active is being changed
            $statusChanged = isset($data['status']);
            $isActiveChanged = isset($data['is_active']);

            // Merge all data
            foreach ($data as $key => $value) {
                if (in_array($key, ['name', 'phone', 'address', 'governorate', 'city', 'status', 'is_active', 'activation_expires_at'])) {
                    $updateData[$key] = $value;
                }
            }

            // Update last_status_change_at if status or is_active changed
            if ($statusChanged || $isActiveChanged) {
                $updateData['last_status_change_at'] = new \DateTime();
            }

            return $this->updateDocument($firebaseUid, $updateData);
        } catch (\Exception $e) {
            Log::error('Error updating client data in Firestore: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Convert Firestore Timestamp to Carbon instance or Unix timestamp
     */
    protected function convertTimestamp($timestamp)
    {
        if ($timestamp === null) {
            return null;
        }

        if ($timestamp instanceof \DateTimeInterface) {
            return \Carbon\Carbon::instance($timestamp);
        }

        // If it's already a timestamp (int), return it
        if (is_numeric($timestamp)) {
            return $timestamp;
        }

        return null;
    }

    /**
     * Get debts from Firestore with optional filter
     */
    public function getDebts(array $filter = []): array
    {
        try {
            $endpoint = "/{$this->debtsCollection}";
            $response = $this->makeRequest('GET', $endpoint);

            $debts = [];

            if (isset($response['documents'])) {
                foreach ($response['documents'] as $document) {
                    $documentId = basename($document['name']);
                    $fields = [];

                    if (isset($document['fields'])) {
                        foreach ($document['fields'] as $key => $value) {
                            $fields[$key] = $this->fromFirestoreValue($value);
                        }
                    }

                    $fields['id'] = $documentId;

                    // Apply filters
                    $include = true;
                    if (!empty($filter)) {
                        if (isset($filter['isFullyPaid']) && ($fields['isFullyPaid'] ?? false) !== $filter['isFullyPaid']) {
                            $include = false;
                        }
                        if (isset($filter['clientUid']) && ($fields['clientUid'] ?? null) !== $filter['clientUid']) {
                            $include = false;
                        }
                        if (isset($filter['dueDateBefore'])) {
                            $dueDate = $fields['dueDate'] ?? null;
                            if ($dueDate instanceof \DateTime) {
                                $dueDate = $dueDate->getTimestamp();
                            }
                            $beforeDate = $filter['dueDateBefore'] instanceof \DateTime 
                                ? $filter['dueDateBefore']->getTimestamp() 
                                : strtotime($filter['dueDateBefore']);
                            if ($dueDate >= $beforeDate) {
                                $include = false;
                            }
                        }
                        if (isset($filter['dueDateAfter'])) {
                            $dueDate = $fields['dueDate'] ?? null;
                            if ($dueDate instanceof \DateTime) {
                                $dueDate = $dueDate->getTimestamp();
                            }
                            $afterDate = $filter['dueDateAfter'] instanceof \DateTime 
                                ? $filter['dueDateAfter']->getTimestamp() 
                                : strtotime($filter['dueDateAfter']);
                            if ($dueDate < $afterDate) {
                                $include = false;
                            }
                        }
                    }

                    if ($include) {
                        $debts[] = $fields;
                    }
                }
            }

            return $debts;
        } catch (\Exception $e) {
            Log::error('Error fetching debts from Firestore: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get products from Firestore with optional filter
     */
    public function getProducts(array $filter = []): array
    {
        try {
            $endpoint = "/{$this->productsCollection}";
            $response = $this->makeRequest('GET', $endpoint);

            $products = [];

            if (isset($response['documents'])) {
                foreach ($response['documents'] as $document) {
                    $documentId = basename($document['name']);
                    $fields = [];

                    if (isset($document['fields'])) {
                        foreach ($document['fields'] as $key => $value) {
                            $fields[$key] = $this->fromFirestoreValue($value);
                        }
                    }

                    $fields['id'] = $documentId;

                    // Apply filters
                    $include = true;
                    if (!empty($filter)) {
                        if (isset($filter['clientUid']) && ($fields['clientUid'] ?? null) !== $filter['clientUid']) {
                            $include = false;
                        }
                        if (isset($filter['lowStockOnly'])) {
                            $remaining = $fields['remainingQuantity'] ?? 0;
                            $minimum = $fields['minQuantity'] ?? 0;
                            if ($remaining > $minimum || $remaining <= 0) {
                                $include = false;
                            }
                        }
                    }

                    if ($include) {
                        $products[] = $fields;
                    }
                }
            }

            return $products;
        } catch (\Exception $e) {
            Log::error('Error fetching products from Firestore: ' . $e->getMessage());
            return [];
        }
    }
}
