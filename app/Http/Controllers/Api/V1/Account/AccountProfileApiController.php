<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AccountProfileApiController extends Controller
{
    public function show(): JsonResponse
    {
        $user = auth()->user();
        $clientProfile = $user->clientProfile;

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'client_profile' => $clientProfile ? [
                    'id' => $clientProfile->id,
                    'full_name' => $clientProfile->full_name,
                    'city' => $clientProfile->city,
                    'address_line' => $clientProfile->address_line,
                ] : null,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ]);

        $user->fill($data)->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'timezone' => $user->timezone,
            ],
        ]);
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'avatar' => ['required', 'image', 'max:4096'],
        ]);

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $validated['avatar']->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Avatar updated',
            'data' => [
                'avatar' => $path,
                'avatar_url' => Storage::disk('public')->url($path),
            ],
        ]);
    }
}
