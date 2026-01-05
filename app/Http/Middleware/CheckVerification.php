<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckVerification {

    public function handle($request, Closure $next)
    {
        $merchant = Auth::guard('merchant')->user();

        if (!$merchant || !$merchant->isVerified()) {
            return response()->json([
                    'message' => 'complete the verification process .',
                    'success' => false,
                ], 403
            );
        }

        return $next($request);
    }
}