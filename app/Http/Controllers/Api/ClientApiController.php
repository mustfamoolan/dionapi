<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Product;
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
                'status' => 'pending', // Default status is pending
                'is_active' => false, // Keep for backward compatibility
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

            // Check client status
            if ($client->isBanned()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حسابك محظور. يرجى التواصل مع الدعم.'
                ], 403);
            }

            if ($client->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حسابك في قائمة الانتظار. يرجى انتظار التفعيل من الإدارة.'
                ], 403);
            }

            if (!$client->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حسابك غير مفعل أو انتهت مدة التفعيل. يرجى التواصل مع الدعم.'
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

    /**
     * Get client status
     */
    public function getStatus(Request $request)
    {
        $client = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $client->status,
                'activation_expires_at' => $client->activation_expires_at,
                'is_expired' => $client->isActivationExpired(),
                'is_active' => $client->isActive(),
                'is_pending' => $client->isPending(),
                'is_banned' => $client->isBanned(),
            ]
        ], 200);
    }

    /**
     * Get client products
     */
    public function getProducts(Request $request)
    {
        $client = $request->user();
        $products = $client->products()->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products
            ]
        ], 200);
    }

    /**
     * Get single product
     */
    public function getProduct(Request $request, $id)
    {
        $client = $request->user();
        $product = Product::where('client_id', $client->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'product' => $product
            ]
        ], 200);
    }

    /**
     * Store new product
     */
    public function storeProduct(Request $request)
    {
        $client = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku,NULL,id,client_id,' . $client->id,
            'purchase_price' => 'required|numeric|min:0',
            'wholesale_price' => 'required|numeric|min:0',
            'retail_price' => 'required|numeric|min:0',
            'unit_type' => 'required|in:weight,piece,carton',
            'weight' => 'required_if:unit_type,weight|nullable|numeric|min:0',
            'weight_unit' => 'required_if:unit_type,weight|nullable|in:kg,g',
            'pieces_per_carton' => 'required_if:unit_type,carton|nullable|integer|min:1',
            'piece_price_in_carton' => 'required_if:unit_type,carton|nullable|numeric|min:0',
            'total_quantity' => 'required|numeric|min:0',
            'remaining_quantity' => 'required|numeric|min:0|max:' . $request->total_quantity,
            'min_quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $product = Product::create([
                'client_id' => $client->id,
                'name' => $request->name,
                'sku' => $request->sku,
                'purchase_price' => $request->purchase_price,
                'wholesale_price' => $request->wholesale_price,
                'retail_price' => $request->retail_price,
                'unit_type' => $request->unit_type,
                'weight' => $request->unit_type === 'weight' ? $request->weight : null,
                'weight_unit' => $request->unit_type === 'weight' ? $request->weight_unit : null,
                'pieces_per_carton' => $request->unit_type === 'carton' ? $request->pieces_per_carton : null,
                'piece_price_in_carton' => $request->unit_type === 'carton' ? $request->piece_price_in_carton : null,
                'total_quantity' => $request->total_quantity,
                'remaining_quantity' => $request->remaining_quantity,
                'min_quantity' => $request->min_quantity,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة المنتج بنجاح',
                'data' => [
                    'product' => $product
                ]
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Product creation error: ' . $e->getMessage(), [
                'client_id' => $client->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة المنتج. يرجى المحاولة مرة أخرى.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update product
     */
    public function updateProduct(Request $request, $id)
    {
        $client = $request->user();
        $product = Product::where('client_id', $client->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku,' . $id . ',id,client_id,' . $client->id,
            'purchase_price' => 'required|numeric|min:0',
            'wholesale_price' => 'required|numeric|min:0',
            'retail_price' => 'required|numeric|min:0',
            'unit_type' => 'required|in:weight,piece,carton',
            'weight' => 'required_if:unit_type,weight|nullable|numeric|min:0',
            'weight_unit' => 'required_if:unit_type,weight|nullable|in:kg,g',
            'pieces_per_carton' => 'required_if:unit_type,carton|nullable|integer|min:1',
            'piece_price_in_carton' => 'required_if:unit_type,carton|nullable|numeric|min:0',
            'total_quantity' => 'required|numeric|min:0',
            'remaining_quantity' => 'required|numeric|min:0|max:' . $request->total_quantity,
            'min_quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $product->update([
                'name' => $request->name,
                'sku' => $request->sku,
                'purchase_price' => $request->purchase_price,
                'wholesale_price' => $request->wholesale_price,
                'retail_price' => $request->retail_price,
                'unit_type' => $request->unit_type,
                'weight' => $request->unit_type === 'weight' ? $request->weight : null,
                'weight_unit' => $request->unit_type === 'weight' ? $request->weight_unit : null,
                'pieces_per_carton' => $request->unit_type === 'carton' ? $request->pieces_per_carton : null,
                'piece_price_in_carton' => $request->unit_type === 'carton' ? $request->piece_price_in_carton : null,
                'total_quantity' => $request->total_quantity,
                'remaining_quantity' => $request->remaining_quantity,
                'min_quantity' => $request->min_quantity,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث المنتج بنجاح',
                'data' => [
                    'product' => $product->fresh()
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Product update error: ' . $e->getMessage(), [
                'product_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث المنتج. يرجى المحاولة مرة أخرى.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Delete product
     */
    public function deleteProduct(Request $request, $id)
    {
        $client = $request->user();
        $product = Product::where('client_id', $client->id)->findOrFail($id);

        try {
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المنتج بنجاح'
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Product deletion error: ' . $e->getMessage(), [
                'product_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المنتج. يرجى المحاولة مرة أخرى.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get low stock products
     */
    public function getLowStockProducts(Request $request)
    {
        $client = $request->user();
        $products = $client->products()
            ->where('is_low_stock', true)
            ->orderBy('remaining_quantity', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products,
                'count' => $products->count()
            ]
        ], 200);
    }
}
