<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $clientProfile = $user->clientProfile;

        return view('account.profile.edit', compact('user', 'clientProfile'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $user->fill($data);
        $user->save();

        return redirect()
            ->route('account.profile.edit')
            ->with('status', 'Профиль обновлён');
    }
}
