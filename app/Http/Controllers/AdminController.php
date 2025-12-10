<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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
        $clients = Client::select(['id', 'firebase_uid', 'name', 'email', 'phone', 'photo_url', 'provider', 'is_active', 'last_login_at', 'created_at'])
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
                    'is_active' => $client->is_active ? '<span class="badge bg-success">نشط</span>' : '<span class="badge bg-danger">غير نشط</span>',
                    'last_login_at' => $client->last_login_at ? $client->last_login_at->format('Y-m-d H:i') : '-',
                    'created_at' => $client->created_at->format('Y-m-d'),
                ];
            })
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
     * Show settings page
     */
    public function settings()
    {
        return view('admin.settings');
    }
}

