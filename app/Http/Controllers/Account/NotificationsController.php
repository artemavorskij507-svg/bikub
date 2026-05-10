<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\SocialCareNotificationSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationsController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        $settings = $user->socialCareNotificationSettings
            ?? new SocialCareNotificationSettings([
                'user_id' => $user->id,
                'notify_care_order_created' => true,
                'notify_care_plan_created' => true,
                'notify_visit_status_changes' => true,
                'notify_visit_reports' => true,
                'notify_emergency' => true,
                'notify_reschedule_requests' => true,
            ]);

        return view('account.notifications.edit', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'notify_care_order_created' => ['nullable', 'boolean'],
            'notify_care_plan_created' => ['nullable', 'boolean'],
            'notify_visit_status_changes' => ['nullable', 'boolean'],
            'notify_visit_reports' => ['nullable', 'boolean'],
            'notify_emergency' => ['nullable', 'boolean'],
            'notify_reschedule_requests' => ['nullable', 'boolean'],
        ]);

        $settings = $user->socialCareNotificationSettings ?? new SocialCareNotificationSettings([
            'user_id' => $user->id,
        ]);

        foreach ($data as $key => $value) {
            $settings->{$key} = (bool) $value;
        }

        $settings->save();

        return redirect()
            ->route('account.notifications.edit')
            ->with('status', 'Настройки уведомлений обновлены');
    }
}
