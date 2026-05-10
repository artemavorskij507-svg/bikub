<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Support\Facades\Log;

trait ReturnsNotImplementedJson
{
    public function __call($name, $arguments)
    {
        Log::warning('Stub API endpoint called', [
            'controller' => static::class,
            'method' => $name,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'This endpoint is not implemented yet.',
            'endpoint' => static::class.'@'.$name,
        ], 501);
    }
}
