<?php

namespace App\Http\Controllers\Api\V1\Helper\Concerns;

use App\Models\SocialHelperProfile;
use App\Models\User;

trait ResolvesHelper
{
    protected function helperProfile(): SocialHelperProfile
    {
        /** @var User $user */
        $user = auth()->user();

        $profile = $user->socialHelperProfile()
            ->where('is_active', true)
            ->first();

        if (! $profile) {
            abort(403, 'Not a social helper or inactive.');
        }

        return $profile;
    }
}
