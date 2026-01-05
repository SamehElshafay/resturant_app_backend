<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle($request, Closure $next, $permission)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin || !$admin->hasPermission($permission)) {
            return response()->json([
                    'message' => 'Forbidden: You do not have permission.',
                    'success' => false,
                ], 403
            );
        }

        return $next($request);
    }
}