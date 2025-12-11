<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

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
        return view('admin.clients');
    }

    /**
     * Get clients data for DataTable
     */
    public function getClients(Request $request)
    {
        $clients = $this->firebaseService->getAllClients();

        return response()->json([
            'data' => array_map(function ($client) {
                $photoUrl = $client['photo_url'] ?? asset('assets/images/avatar/dummy-avatar.jpg');
                $status = $client['status'];

                return [
                    'firebase_uid' => $client['firebase_uid'],
                    'name' => '<div class="d-flex gap-3 justify-content-start align-items-center">
                        <div class="avatar avatar-sm">
                            <img src="' . $photoUrl . '" alt="Avatar" class="avatar-item avatar rounded-circle">
                        </div>
                        <div class="d-flex flex-column">
                            <p class="mb-0 fw-medium">' . htmlspecialchars($client['name']) . '</p>
                        </div>
                    </div>',
                    'email' => $client['email'],
                    'phone' => $client['phone'] ?? '-',
                    'address' => $client['address'] ?? '-',
                    'governorate' => $client['governorate'] ?? '-',
                    'city' => $client['city'] ?? '-',
                    'provider' => $this->getProviderBadge($client['provider']),
                    'status' => $this->getStatusBadge($status),
                    'status_value' => $status,
                    'is_active' => $client['is_active'] ? '<span class="badge bg-success">نعم</span>' : '<span class="badge bg-danger">لا</span>',
                    'activation_expires_at' => $client['activation_expires_at'] ? (is_int($client['activation_expires_at']) ? date('Y-m-d', $client['activation_expires_at']) : $client['activation_expires_at']->format('Y-m-d')) : '-',
                    'last_login_at' => $client['last_login_at'] ? (is_int($client['last_login_at']) ? date('Y-m-d H:i', $client['last_login_at']) : $client['last_login_at']->format('Y-m-d H:i')) : '-',
                    'created_at' => $client['created_at'] ? (is_int($client['created_at']) ? date('Y-m-d', $client['created_at']) : $client['created_at']->format('Y-m-d')) : '-',
                    'actions' => $this->getClientActionButtons($client['firebase_uid'], $client['name'], $status),
                ];
            }, $clients)
        ]);
    }

    /**
     * Get client by Firebase UID
     */
    public function getClient($firebaseUid)
    {
        $client = $this->firebaseService->getClient($firebaseUid);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'العميل غير موجود'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'client' => [
                'firebase_uid' => $client['firebase_uid'],
                'name' => $client['name'],
                'email' => $client['email'],
                'phone' => $client['phone'] ?? '',
                'address' => $client['address'] ?? '',
                'governorate' => $client['governorate'] ?? '',
                'city' => $client['city'] ?? '',
                'status' => $client['status'],
                'is_active' => $client['is_active'],
                'activation_expires_at' => $client['activation_expires_at'] ? (is_int($client['activation_expires_at']) ? date('Y-m-d', $client['activation_expires_at']) : $client['activation_expires_at']->format('Y-m-d')) : null,
            ]
        ]);
    }

    /**
     * Update client status
     */
    public function updateClientStatus(Request $request, $firebaseUid)
    {
        $request->validate([
            'status' => 'required|in:pending,active,banned,expired',
            'months' => 'required_if:status,active|integer|min:1|max:120',
        ]);

        $client = $this->firebaseService->getClient($firebaseUid);

        if (!$client) {
                return response()->json([
                    'success' => false,
                'message' => 'العميل غير موجود'
            ], 404);
        }

        $oldStatus = $client['status'];
        $months = $request->status === 'active' ? ($request->months ?? 1) : null;

        $success = $this->firebaseService->updateClientStatus($firebaseUid, $request->status, $months);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة العميل'
            ], 500);
        }

        $messages = [
            'active' => "تم تفعيل العميل لمدة {$months} شهر بنجاح",
            'banned' => 'تم حظر العميل بنجاح',
            'pending' => 'تم وضع العميل في قائمة الانتظار بنجاح',
            'expired' => 'تم تعيين حالة العميل كأنهاء اشتراك بنجاح',
        ];

        $updatedClient = $this->firebaseService->getClient($firebaseUid);

        return response()->json([
            'success' => true,
            'message' => $messages[$request->status] ?? 'تم تحديث الحالة بنجاح',
            'client' => [
                'firebase_uid' => $updatedClient['firebase_uid'],
                'status' => $updatedClient['status'],
                'activation_expires_at' => $updatedClient['activation_expires_at'] ? (is_int($updatedClient['activation_expires_at']) ? date('Y-m-d', $updatedClient['activation_expires_at']) : $updatedClient['activation_expires_at']->format('Y-m-d')) : null,
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
     * Update client data
     */
    public function updateClient(Request $request, $firebaseUid)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'governorate' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'status' => 'nullable|in:pending,active,banned,expired',
            'is_active' => 'nullable|boolean',
            'activation_expires_at' => 'nullable|date',
        ]);

        $client = $this->firebaseService->getClient($firebaseUid);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'العميل غير موجود'
            ], 404);
        }

        $updateData = [];

        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }
        if ($request->has('phone')) {
            $updateData['phone'] = $request->phone ?: null;
        }
        if ($request->has('address')) {
            $updateData['address'] = $request->address ?: null;
        }
        if ($request->has('governorate')) {
            $updateData['governorate'] = $request->governorate ?: null;
        }
        if ($request->has('city')) {
            $updateData['city'] = $request->city ?: null;
        }
        if ($request->has('status')) {
            $updateData['status'] = $request->status;
        }
        if ($request->has('is_active')) {
            $updateData['is_active'] = (bool) $request->is_active;
        }
        if ($request->has('activation_expires_at') && $request->activation_expires_at) {
            try {
                $updateData['activation_expires_at'] = new \DateTime($request->activation_expires_at);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'تاريخ انتهاء التفعيل غير صحيح'
                ], 422);
            }
        }

        if (empty($updateData)) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم إرسال أي بيانات للتحديث'
            ], 422);
        }

        $success = $this->firebaseService->updateClientData($firebaseUid, $updateData);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث بيانات العميل'
            ], 500);
        }

        $updatedClient = $this->firebaseService->getClient($firebaseUid);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات العميل بنجاح',
            'client' => [
                'firebase_uid' => $updatedClient['firebase_uid'],
                'name' => $updatedClient['name'],
                'email' => $updatedClient['email'],
                'phone' => $updatedClient['phone'] ?? '',
                'address' => $updatedClient['address'] ?? '',
                'governorate' => $updatedClient['governorate'] ?? '',
                'city' => $updatedClient['city'] ?? '',
                'status' => $updatedClient['status'],
                'is_active' => $updatedClient['is_active'],
                'activation_expires_at' => $updatedClient['activation_expires_at'] ? (is_int($updatedClient['activation_expires_at']) ? date('Y-m-d', $updatedClient['activation_expires_at']) : $updatedClient['activation_expires_at']->format('Y-m-d')) : null,
            ]
        ]);
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
     * Generate action buttons HTML for clients
     */
    private function getClientActionButtons($firebaseUid, $clientName, $status)
    {
        $buttons = '<div class="hstack gap-2">';

        $buttons .= '<button type="button" class="btn btn-sm btn-primary edit-client" data-firebase-uid="' . $firebaseUid . '" title="تعديل">
            <i class="ri-edit-2-line"></i>
        </button>';

        if ($status != 'active') {
            $buttons .= '<button type="button" class="btn btn-sm btn-success activate-client" data-firebase-uid="' . $firebaseUid . '" data-name="' . htmlspecialchars($clientName) . '" title="تفعيل">
                <i class="ri-check-line"></i>
            </button>';
        }

        if ($status != 'banned') {
            $buttons .= '<button type="button" class="btn btn-sm btn-danger ban-client" data-firebase-uid="' . $firebaseUid . '" data-name="' . htmlspecialchars($clientName) . '" title="حظر">
                <i class="ri-close-line"></i>
            </button>';
        }

        if ($status != 'pending') {
            $buttons .= '<button type="button" class="btn btn-sm btn-warning pending-client" data-firebase-uid="' . $firebaseUid . '" data-name="' . htmlspecialchars($clientName) . '" title="وضع في الانتظار">
                <i class="ri-time-line"></i>
            </button>';
        }

        if ($status != 'expired') {
            $buttons .= '<button type="button" class="btn btn-sm btn-info expire-client" data-firebase-uid="' . $firebaseUid . '" data-name="' . htmlspecialchars($clientName) . '" title="انتهى الاشتراك">
                <i class="ri-calendar-close-line"></i>
            </button>';
        }

        $buttons .= '</div>';

        return $buttons;
    }

    /**
     * Show settings page
     */
    public function settings()
    {
        return view('admin.settings');
    }
}

