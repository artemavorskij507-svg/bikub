<?php

namespace App\Modules\Logistics\Http\Middleware;

use App\Modules\Logistics\Models\DeliveryPersonnel;
use Closure;
use Illuminate\Http\Request;

class EnsureDeliveryPersonnel
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || ! DeliveryPersonnel::where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Delivery personnel access required.'], 403);
        }

        return $next($request);
    }
}
