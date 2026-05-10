<?php

namespace App\Http\Controllers;

use App\Services\Identity\IdentityVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdentityMockController extends Controller
{
    public function start(Request $request, IdentityVerificationService $identity): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $identity->start($request->all())]);
    }

    public function complete(Request $request, IdentityVerificationService $identity): JsonResponse
    {
        $data = $request->validate(['session_id' => ['required', 'string']]);
        return response()->json(['success' => true, 'data' => $identity->complete($data['session_id'], $request->all())]);
    }
}

