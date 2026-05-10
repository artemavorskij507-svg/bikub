<?php

namespace App\Http\Controllers\Lk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $preferences = $user->preferences ?? [];

        $settings = [
            'notify_orders' => (bool) ($preferences['notify_orders'] ?? true),
            'notify_payouts' => (bool) ($preferences['notify_payouts'] ?? true),
            'notify_system' => (bool) ($preferences['notify_system'] ?? true),
            'interface_lang' => $preferences['interface_lang'] ?? 'ru',
            'interface_theme' => $preferences['interface_theme'] ?? 'light',
        ];

        return view('lk.settings', [
            'user' => $user,
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'notify_orders' => ['nullable', 'boolean'],
            'notify_payouts' => ['nullable', 'boolean'],
            'notify_system' => ['nullable', 'boolean'],
            'interface_lang' => ['nullable', 'in:ru,uk,en,no'],
            'interface_theme' => ['nullable', 'in:light,dark,system'],
        ]);

        $user = $request->user();
        $preferences = $user->preferences ?? [];

        $preferences['notify_orders'] = (bool) ($data['notify_orders'] ?? false);
        $preferences['notify_payouts'] = (bool) ($data['notify_payouts'] ?? false);
        $preferences['notify_system'] = (bool) ($data['notify_system'] ?? true);
        $preferences['interface_lang'] = $data['interface_lang'] ?? ($preferences['interface_lang'] ?? 'ru');
        $preferences['interface_theme'] = $data['interface_theme'] ?? ($preferences['interface_theme'] ?? 'light');

        $user->preferences = $preferences;
        $user->save();

        return redirect()
            ->route('lk.settings')
            ->with('status', 'Настройки сохранены.');
    }
}
