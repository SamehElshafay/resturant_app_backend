<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TokenExpirationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user) {
            
            // Get role from relation instead of risking the string accessor
            $role = $user->role()->first(); 
            if (!$role) {
                $role = $user->roles()->first();
            }

            if ($role) {
                $lifetimeMinutes = $role->token_lifetime_minutes;
                
                // If lifetime is null, it means "Unlimited / Life Time"
                if (is_null($lifetimeMinutes)) {
                    return $next($request);
                }
                
                if ($request->expectsJson() || $request->is('api/*')) {
                    // Sanctum API Token check
                    $token = $user->currentAccessToken();
                    if ($token && $token->created_at) {
                        $expiration = Carbon::parse($token->created_at)->addMinutes($lifetimeMinutes);
                        if (now()->greaterThan($expiration)) {
                            $token->delete(); // Revoke expired token
                            return response()->json(['message' => 'Token has expired.'], 401);
                        }
                    }
                } else {
                    // Web Session check
                    $lastActivity = session('last_activity_time');
                    if ($lastActivity) {
                        $expiration = Carbon::parse($lastActivity)->addMinutes($lifetimeMinutes);
                        if (now()->greaterThan($expiration)) {
                            Auth::logout();
                            session()->flush();
                            return redirect()->route('login')->with('error', 'Session expired due to role settings.');
                        }
                    }
                    session(['last_activity_time' => now()]);
                }
            }
        }

        return $next($request);
    }
}
