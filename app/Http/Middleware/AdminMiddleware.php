<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return $this->isApiRequest($request)
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('admin.login');
        }

        $user = auth()->user();

        // Check if user is banned
        if ($user->status === 'banned') {
            auth()->logout();
            
            return $this->isApiRequest($request)
                ? response()->json(['message' => 'Your account has been banned.'], 403)
                : redirect()->route('admin.login')->withErrors(['email' => 'Tài khoản của bạn đã bị khóa.']);
        }

        // Check if user is admin
        if ($user->role !== 'admin') {
            return $this->isApiRequest($request)
                ? response()->json(['message' => 'Unauthorized. Admin access required.'], 403)
                : redirect('/')->withErrors(['error' => 'Bạn không có quyền truy cập.']);
        }

        return $next($request);
    }

    /**
     * Determine if request is API request
     */
    private function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || 
               $request->expectsJson() || 
               $request->header('Accept') === 'application/json';
    }
}