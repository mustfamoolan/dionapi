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
        $validator = Validator::make($request->all(), [
            'firebase_uid' => 'required|string|unique:clients,firebase_uid',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'photo_url' => 'nullable|url',
            'provider' => 'required|string|in:google,facebook,apple',
            'provider_id' => 'nullable|string',
            'device_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $client = Client::create([
            'firebase_uid' => $request->firebase_uid,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'photo_url' => $request->photo_url,
            'provider' => $request->provider,
            'provider_id' => $request->provider_id,
            'device_token' => $request->device_token,
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
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

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
        $client->update([
            'device_token' => $request->device_token ?? $client->device_token,
            'last_login_at' => now(),
        ]);

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

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'photo_url' => 'nullable|url',
            'device_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $client->update($request->only(['name', 'phone', 'photo_url', 'device_token']));

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
