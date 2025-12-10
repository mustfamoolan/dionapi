<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('auth-signin');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            Auth::login($user, $request->has('remember'));

            // Redirect to dashboard
            return redirect()->route('admin.dashboard')->with('success', 'تم تسجيل الدخول بنجاح');
        }

        return back()->withErrors([
            'phone' => 'رقم الهاتف أو كلمة المرور غير صحيحة.',
        ])->withInput($request->only('phone'));
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}

