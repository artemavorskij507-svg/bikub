<?php

namespace App\Http\Controllers\Api\V1\Helper;

use App\Http\Controllers\Api\V1\Helper\Concerns\ResolvesHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelperMeController extends Controller
{
    use ResolvesHelper;

    public function show(Request $request): JsonResponse
    {
        $helper = $this->helperProfile();

        $helper->loadMissing(['user', 'organization']);

        return response()->json([
            'data' => [
                'id' => $helper->id,
                'user_id' => $helper->user_id,
                'name' => $helper->display_name ?? $helper->user->name,
                'level' => $helper->level,
                'bio' => $helper->bio,
                'skills' => $helper->skills ?? [],
                'has_police_certificate' => $helper->has_police_certificate,
                'rating_avg' => $helper->rating_avg,
                'rating_count' => $helper->rating_count,
                'is_active' => $helper->is_active,
                'organization' => $helper->organization ? [
                    'id' => $helper->organization->id,
                    'name' => $helper->organization->name,
                ] : null,
            ],
        ]);
    }
}
