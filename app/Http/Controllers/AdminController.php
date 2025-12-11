<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * Show dashboard page
     */
    public function dashboard()
    {
        return view('admin.dashboard');
    }

    /**
     * Show users page
     */
    public function users()
    {
        return view('admin.users');
    }

    /**
     * Get users data for DataTable
     */
    public function getUsers(Request $request)
    {
        $users = User::select(['id', 'name', 'phone', 'role', 'avatar', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'role' => $user->role === 'admin' ? 'مدير' : 'موظف',
                    'role_badge' => $user->role === 'admin'
                        ? '<span class="badge bg-danger">مدير</span>'
                        : '<span class="badge bg-primary">موظف</span>',
                    'avatar' => $user->avatar
                        ? asset('storage/' . $user->avatar)
                        : asset('assets/images/avatar/avatar-1.jpg'),
                    'created_at' => $user->created_at->format('Y-m-d'),
                    'actions' => $this->getActionButtons($user->id)
                ];
            })
        ]);
    }

    /**
     * Store new user
     */
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,employee',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'avatar' => $avatarPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المستخدم بنجاح',
            'user' => $user
        ]);
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validationRules = [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone,' . $id,
            'role' => 'required|in:admin,employee',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        // Only validate password if it's provided
        if ($request->filled('password')) {
            $validationRules['password'] = 'required|string|min:8';
        }

        $request->validate($validationRules);

        $data = [
            'name' => $request->name,
            'phone' => $request->phone,
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المستخدم بنجاح',
            'user' => $user
        ]);
    }

    /**
     * Delete user
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        // Don't allow deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك حذف حسابك الخاص'
            ], 400);
        }

        // Delete avatar if exists
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المستخدم بنجاح'
        ]);
    }

    /**
     * Get user by ID
     */
    public function getUser($id)
    {
        $user = User::findOrFail($id);
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'role' => $user->role,
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
            ]
        ]);
    }

    /**
     * Generate action buttons HTML
     */
    private function getActionButtons($userId)
    {
        return '
            <div class="hstack gap-2">
                <button class="btn btn-sm btn-light-primary border-primary edit-user" data-id="' . $userId . '" data-bs-toggle="tooltip" title="تعديل">
                    <i class="ri-edit-2-line"></i>
                </button>
                <button class="btn btn-sm btn-light-danger border-danger delete-user" data-id="' . $userId . '" data-bs-toggle="tooltip" title="حذف">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        ';
    }

    /**
     * Show clients page
     */
    public function clients()
    {
        $clients = Client::all();
        return view('admin.clients', compact('clients'));
    }

    /**
     * Get clients data for DataTable
     */
    public function getClients(Request $request)
    {
        $clients = Client::select(['id', 'firebase_uid', 'name', 'email', 'phone', 'photo_url', 'provider', 'status', 'activation_expires_at', 'last_login_at', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $clients->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'phone' => $client->phone ?? '-',
                    'provider' => $this->getProviderBadge($client->provider),
                    'photo_url' => $client->photo_url ?? asset('assets/images/avatar/dummy-avatar.jpg'),
                    'status' => $this->getStatusBadge($client->status),
                    'status_value' => $client->status,
                    'activation_expires_at' => $client->activation_expires_at ? $client->activation_expires_at->format('Y-m-d') : '-',
                    'last_login_at' => $client->last_login_at ? $client->last_login_at->format('Y-m-d H:i') : '-',
                    'created_at' => $client->created_at->format('Y-m-d'),
                ];
            })
        ]);
    }

    /**
     * Get client by ID
     */
    public function getClient($id)
    {
        $client = Client::findOrFail($id);
        return response()->json([
            'success' => true,
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'status' => $client->status,
                'activation_expires_at' => $client->activation_expires_at ? $client->activation_expires_at->format('Y-m-d') : null,
            ]
        ]);
    }

    /**
     * Update client status
     */
    public function updateClientStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,active,banned,expired',
            'months' => 'required_if:status,active|integer|min:1|max:120',
        ]);

        $client = Client::findOrFail($id);

        $oldStatus = $client->status;

        switch ($request->status) {
            case 'active':
                $months = $request->months ?? 1;
                $client->activate($months);
                $message = "تم تفعيل العميل لمدة {$months} شهر بنجاح";
                $notificationTitle = 'تم تفعيل حسابك';
                $notificationBody = "تم تفعيل حسابك بنجاح لمدة {$months} شهر. يمكنك الآن استخدام التطبيق.";
                break;
            case 'banned':
                $client->ban();
                $message = 'تم حظر العميل بنجاح';
                $notificationTitle = 'تم حظر حسابك';
                $notificationBody = 'تم حظر حسابك. يرجى التواصل مع الدعم لمزيد من المعلومات.';
                break;
            case 'pending':
                $client->setPending();
                $message = 'تم وضع العميل في قائمة الانتظار بنجاح';
                $notificationTitle = 'تغيير حالة الحساب';
                $notificationBody = 'تم وضع حسابك في قائمة الانتظار. يرجى انتظار التفعيل من الإدارة.';
                break;
            case 'expired':
                $client->setExpired();
                $message = 'تم تعيين حالة العميل كأنهاء اشتراك بنجاح';
                $notificationTitle = 'انتهت مدة اشتراكك';
                $notificationBody = 'انتهت مدة اشتراكك. يرجى تجديد الاشتراك للاستمرار في استخدام التطبيق.';
                break;
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'حالة غير صحيحة'
                ], 400);
        }

        // Send push notification if status changed and device token exists
        if ($oldStatus !== $client->status && $client->device_token) {
            $this->sendStatusChangeNotification($client, $notificationTitle, $notificationBody);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'client' => [
                'id' => $client->id,
                'status' => $client->status,
                'activation_expires_at' => $client->activation_expires_at ? $client->activation_expires_at->format('Y-m-d') : null,
            ]
        ]);
    }

    /**
     * Get status badge HTML
     */
    private function getStatusBadge($status)
    {
        $badges = [
            'pending' => '<span class="badge bg-warning">في الانتظار</span>',
            'active' => '<span class="badge bg-success">مفعل</span>',
            'banned' => '<span class="badge bg-danger">محظور</span>',
            'expired' => '<span class="badge bg-info">انتهى الاشتراك</span>',
        ];

        return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
    }

    /**
     * Send push notification to client when status changes
     */
    private function sendStatusChangeNotification(Client $client, string $title, string $body)
    {
        if (!$client->device_token) {
            return;
        }

        $fcmServerKey = env('FCM_SERVER_KEY');

        if (!$fcmServerKey) {
            Log::warning('FCM_SERVER_KEY not configured. Cannot send push notification.');
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $client->device_token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                ],
                'data' => [
                    'type' => 'status_change',
                    'status' => $client->status,
                    'activation_expires_at' => $client->activation_expires_at ? $client->activation_expires_at->toIso8601String() : null,
                    'last_status_change_at' => $client->last_status_change_at ? $client->last_status_change_at->toIso8601String() : null,
                    'timestamp' => now()->toIso8601String(),
                    'is_expired' => $client->isExpired() || $client->isActivationExpired(),
                    'is_subscription_expired' => $client->isExpired(),
                    'is_active' => $client->isActive(),
                    'is_pending' => $client->isPending(),
                    'is_banned' => $client->isBanned(),
                ],
                'priority' => 'high',
            ]);

            if ($response->successful()) {
                Log::info('Push notification sent successfully', [
                    'client_id' => $client->id,
                    'status' => $client->status,
                ]);
            } else {
                Log::error('Failed to send push notification', [
                    'client_id' => $client->id,
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending push notification: ' . $e->getMessage(), [
                'client_id' => $client->id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get provider badge HTML
     */
    private function getProviderBadge($provider)
    {
        $badges = [
            'google' => '<span class="badge bg-danger">Google</span>',
            'facebook' => '<span class="badge bg-primary">Facebook</span>',
            'apple' => '<span class="badge bg-dark">Apple</span>',
        ];

        return $badges[$provider] ?? '<span class="badge bg-secondary">' . $provider . '</span>';
    }

    /**
     * Show settings page
     */
    public function settings()
    {
        return view('admin.settings');
    }

    /**
     * Show client products page
     */
    public function clientProducts($clientId)
    {
        $client = Client::findOrFail($clientId);
        $products = $client->products()->orderBy('created_at', 'desc')->get();
        return view('admin.client-products', compact('client', 'products'));
    }

    /**
     * Get client products data for DataTable
     */
    public function getClientProducts(Request $request, $clientId)
    {
        $products = Product::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'purchase_price' => number_format($product->purchase_price, 2),
                    'wholesale_price' => number_format($product->wholesale_price, 2),
                    'retail_price' => number_format($product->retail_price, 2),
                    'unit_type' => $this->getUnitTypeLabel($product->unit_type),
                    'unit_details' => $product->getFormattedUnit(),
                    'total_quantity' => number_format($product->total_quantity, 2),
                    'remaining_quantity' => number_format($product->remaining_quantity, 2),
                    'min_quantity' => number_format($product->min_quantity, 2),
                    'is_low_stock' => $product->is_low_stock ? '<span class="badge bg-danger">منخفض</span>' : '<span class="badge bg-success">طبيعي</span>',
                ];
            })
        ]);
    }

    /**
     * Store new product
     */
    public function storeProduct(Request $request, $clientId)
    {
        $client = Client::findOrFail($clientId);

        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku,NULL,id,client_id,' . $clientId,
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

        $product = Product::create([
            'client_id' => $clientId,
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
            'product' => $product
        ]);
    }

    /**
     * Update product
     */
    public function updateProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku,' . $id . ',id,client_id,' . $product->client_id,
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
            'product' => $product
        ]);
    }

    /**
     * Delete product
     */
    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المنتج بنجاح'
        ]);
    }

    /**
     * Get product by ID
     */
    public function getProduct($id)
    {
        $product = Product::findOrFail($id);
        return response()->json([
            'success' => true,
            'product' => $product
        ]);
    }

    /**
     * Get unit type label
     */
    private function getUnitTypeLabel($unitType)
    {
        $labels = [
            'weight' => 'وزن',
            'piece' => 'قطعة',
            'carton' => 'كارتون',
        ];

        return $labels[$unitType] ?? $unitType;
    }
}

