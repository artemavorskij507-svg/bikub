<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\RoadsideEmergency;

class RoadsideTrackingController extends Controller
{
    /**
     * Show tracking page for roadside emergency.
     */
    public function show(string $token)
    {
        $emergency = RoadsideEmergency::query()
            ->where('tracking_token', $token)
            ->with([
                'order.assignedUser',
                'order.geoZone',
                'helper.user',
                'partner',
                'customer',
            ])
            ->firstOrFail();

        $timeline = $emergency->buildTimeline();

        // Determine service type label
        $serviceTypeLabel = match ($emergency->incident_type) {
            'tow_needed' => 'Эвакуатор',
            'jump_start' => 'Прикуривание',
            'fuel' => 'Топливо',
            'flat_tire' => 'Прокол колеса',
            'locked_keys' => 'Открытие замка',
            'engine_no_start' => 'Не заводится',
            'accident' => 'ДТП',
            default => 'Помощь на дороге',
        };

        return view('public.roadside-track', [
            'emergency' => $emergency,
            'timeline' => $timeline,
            'serviceTypeLabel' => $serviceTypeLabel,
        ]);
    }
}
