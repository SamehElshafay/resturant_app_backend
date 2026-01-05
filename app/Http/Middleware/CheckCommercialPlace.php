<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckCommercialPlace {

    public function handle($request, Closure $next)
    {
        $merchant = Auth::guard('merchant')->user();

        if (!$merchant || $merchant->commercial_place_id == null) {
            return response()->json([
                    'success' => false,
                    'message' => 'wait for your first place'
                ], 403);
        }

        return $next($request);
    }
}