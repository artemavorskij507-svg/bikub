<?php

namespace App\Http\Controllers\Api\V1\Executor;

use App\Http\Controllers\Controller;
use App\Http\Resources\Executor\ExecutorProfileResource;
use Illuminate\Http\Request;

class ExecutorProfileController extends Controller
{
    public function me(Request $request): ExecutorProfileResource
    {
        $profile = $request->user()->executorProfile;
        $profile->load('user');

        return new ExecutorProfileResource($profile);
    }
}
