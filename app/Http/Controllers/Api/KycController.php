<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KycController extends Controller
{
    /**
     * Upload KYC document
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        return response()->json([
            'error' => 'not_implemented',
            'message' => 'KYC document upload is not yet implemented',
        ], 501);
    }

    /**
     * Get KYC documents
     */
    public function getDocuments(Request $request): JsonResponse
    {
        return response()->json([
            'error' => 'not_implemented',
            'message' => 'KYC documents retrieval is not yet implemented',
        ], 501);
    }

    /**
     * Get KYC status
     */
    public function getStatus(Request $request): JsonResponse
    {
        return response()->json([
            'error' => 'not_implemented',
            'message' => 'KYC status check is not yet implemented',
        ], 501);
    }
}
