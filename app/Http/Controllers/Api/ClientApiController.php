<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientApiController extends Controller
{
    /**
     * Register a new client from Firebase
     */
    public function register(Request $request)
    {
        // Clean and prepare data
        $data = $request->all();
        
        // Handle photo_url - accept empty string as null
        if (isset($data['photo_url']) && empty(trim($data['photo_url']))) {
            $data['photo_url'] = null;
        }

        $validator = Validator::make($data, [
            'firebase_uid' => 'required|string|unique:clients,firebase_uid',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'photo_url' => 'nullable|url|max:500',
            'provider' => 'required|string|in:google,facebook,apple',
            'provider_id' => 'nullable|string|max:255',
            'device_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            \Log::warning('Client registration validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $data
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $client = Client::create([
                'firebase_uid' => $data['firebase_uid'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'photo_url' => $data['photo_url'] ?? null,
                'provider' => $data['provider'],
                'provider_id' => $data['provider_id'] ?? null,
                'device_token' => $data['device_token'] ?? null,
                'is_active' => true,
                'last_login_at' => now(),
            ]);

            $token = $client->createToken('mobile-app')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل العميل بنجاح',
                'data' => [
                    'client' => $client,
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Client registration error: ' . $e->getMessage(), [
                'request_data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Login client (using Firebase UID)
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firebase_uid' => 'required|string',
            'device_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $client = Client::where('firebase_uid', $request->firebase_uid)->first();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'العميل غير موجود. يرجى التسجيل أولاً.'
                ], 404);
            }

            if (!$client->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'حسابك غير نشط. يرجى التواصل مع الدعم.'
                ], 403);
            }

            // Update device token and last login
            $updateData = ['last_login_at' => now()];
            if ($request->has('device_token') && !empty($request->device_token)) {
                $updateData['device_token'] = $request->device_token;
            }
            $client->update($updateData);

            // Revoke all existing tokens and create new one
            $client->tokens()->delete();
            $token = $client->createToken('mobile-app')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
                'data' => [
                    'client' => $client,
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Client login error: ' . $e->getMessage(), [
                'firebase_uid' => $request->firebase_uid,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الدخول. يرجى المحاولة مرة أخرى.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get client profile
     */
    public function profile(Request $request)
    {
        $client = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'client' => $client
            ]
        ], 200);
    }

    /**
     * Update client profile
     */
    public function updateProfile(Request $request)
    {
        $client = $request->user();

        // Clean and prepare data
        $data = $request->all();
        
        // Handle photo_url - accept empty string as null
        if (isset($data['photo_url']) && empty(trim($data['photo_url']))) {
            $data['photo_url'] = null;
        }

        $validator = Validator::make($data, [
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'photo_url' => 'nullable|url|max:500',
            'device_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = [];
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['phone'])) $updateData['phone'] = $data['phone'];
        if (isset($data['photo_url'])) $updateData['photo_url'] = $data['photo_url'];
        if (isset($data['device_token'])) $updateData['device_token'] = $data['device_token'];
        
        $client->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث البيانات بنجاح',
            'data' => [
                'client' => $client->fresh()
            ]
        ], 200);
    }

    /**
     * Logout client
     */
    public function logout(Request $request)
    {
        $client = $request->user();

        // Delete current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح'
        ], 200);
    }

    /**
     * Refresh token
     */
    public function refreshToken(Request $request)
    {
        $client = $request->user();

        // Delete all existing tokens
        $client->tokens()->delete();

        // Create new token
        $token = $client->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التوكن بنجاح',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 200);
    }
}
