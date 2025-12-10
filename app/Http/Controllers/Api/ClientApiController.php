<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProductRequest;
use App\Http\Requests\Api\UpdateProductRequest;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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
        try {
            $client = $request->user();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود أو غير مصرح له.'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'client' => $client
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Get profile error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات الملف الشخصي. يرجى المحاولة مرة أخرى.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update client profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $client = $request->user();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود أو غير مصرح له.'
                ], 401);
            }

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
            ], [
                'name.string' => 'الاسم يجب أن يكون نصاً.',
                'name.max' => 'الاسم يجب ألا يتجاوز 255 حرفاً.',
                'phone.string' => 'رقم الهاتف يجب أن يكون نصاً.',
                'phone.max' => 'رقم الهاتف يجب ألا يتجاوز 20 حرفاً.',
                'photo_url.url' => 'رابط الصورة غير صحيح.',
                'photo_url.max' => 'رابط الصورة يجب ألا يتجاوز 500 حرفاً.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطأ في التحقق من البيانات',
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
        } catch (\Exception $e) {
            \Log::error('Update profile error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث البيانات. يرجى المحاولة مرة أخرى.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
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
        try {
            $client = $request->user();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود أو غير مصرح له.'
                ], 401);
            }

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
        } catch (\Exception $e) {
            \Log::error('Get status error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب حالة الحساب. يرجى المحاولة مرة أخرى.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get client products
     */
    public function getProducts(Request $request)
    {
        try {
            $client = $request->user();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود أو غير مصرح له.'
                ], 401);
            }

            $products = $client->products()->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $products,
                    'count' => $products->count()
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Get products error: ' . $e->getMessage(), [
                'client_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المنتجات. يرجى المحاولة مرة أخرى.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get single product
     */
    public function getProduct(Request $request, $id)
    {
        try {
            $client = $request->user();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود أو غير مصرح له.'
                ], 401);
            }

            $product = Product::where('client_id', $client->id)->find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'المنتج غير موجود أو ليس لديك صلاحية للوصول إليه.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => $product
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Get product error: ' . $e->getMessage(), [
                'product_id' => $id,
                'client_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المنتج. يرجى المحاولة مرة أخرى.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store new product
     */
    public function storeProduct(StoreProductRequest $request)
    {
        try {
            $client = $request->user();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود أو غير مصرح له.'
                ], 401);
            }

            // Validate remaining_quantity doesn't exceed total_quantity
            if ($request->remaining_quantity > $request->total_quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'الكمية المتبقية لا يمكن أن تكون أكبر من الكمية الكلية.',
                    'errors' => [
                        'remaining_quantity' => ['الكمية المتبقية لا يمكن أن تكون أكبر من الكمية الكلية.']
                    ]
                ], 422);
            }

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
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Product creation error: ' . $e->getMessage(), [
                'client_id' => $request->user()?->id,
                'request_data' => $request->all(),
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
    public function updateProduct(UpdateProductRequest $request, $id)
    {
        try {
            $client = $request->user();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود أو غير مصرح له.'
                ], 401);
            }

            $product = Product::where('client_id', $client->id)->find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'المنتج غير موجود أو ليس لديك صلاحية للوصول إليه.'
                ], 404);
            }

            // Validate remaining_quantity doesn't exceed total_quantity
            if ($request->remaining_quantity > $request->total_quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'الكمية المتبقية لا يمكن أن تكون أكبر من الكمية الكلية.',
                    'errors' => [
                        'remaining_quantity' => ['الكمية المتبقية لا يمكن أن تكون أكبر من الكمية الكلية.']
                    ]
                ], 422);
            }

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
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Product update error: ' . $e->getMessage(), [
                'product_id' => $id,
                'client_id' => $request->user()?->id,
                'request_data' => $request->all(),
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
        try {
            $client = $request->user();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود أو غير مصرح له.'
                ], 401);
            }

            $product = Product::where('client_id', $client->id)->find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'المنتج غير موجود أو ليس لديك صلاحية للوصول إليه.'
                ], 404);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المنتج بنجاح'
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Product deletion error: ' . $e->getMessage(), [
                'product_id' => $id,
                'client_id' => $request->user()?->id,
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
        try {
            $client = $request->user();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود أو غير مصرح له.'
                ], 401);
            }

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
        } catch (\Exception $e) {
            \Log::error('Get low stock products error: ' . $e->getMessage(), [
                'client_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المنتجات منخفضة الكمية. يرجى المحاولة مرة أخرى.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
