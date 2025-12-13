<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح - يجب تسجيل الدخول',
            ], 401);
        }

        $user = Auth::user();
        
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح - يجب أن تكون مدير',
            ], 403);
        }

        return $next($request);
    }
}

