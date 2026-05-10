<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\RoadHelperProfile;
use App\Models\RoadsideEmergency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoadsideDispatchController extends Controller
{
    /**
     * Assign helper to roadside emergency.
     */
    public function assignHelper(Request $request)
    {
        $data = $request->validate([
            'emergency_id' => ['required', 'exists:roadside_emergencies,id'],
            'helper_id' => ['required', 'exists:road_helper_profiles,id'],
        ]);

        try {
            DB::beginTransaction();

            $emergency = RoadsideEmergency::with('order')->findOrFail($data['emergency_id']);
            $helper = RoadHelperProfile::findOrFail($data['helper_id']);

            // 1) Привязываем helper к RoadsideEmergency
            $emergency->road_helper_id = $helper->id;
            if ($emergency->status === 'new') {
                $emergency->status = 'assigned';
            }
            $emergency->save();

            // 2) Привязываем Order к пользователю-хелперу
            if ($emergency->order && $helper->user_id) {
                $emergency->order->assigned_to = $helper->user_id;
                if ($emergency->order->status === 'pending') {
                    $emergency->order->status = 'assigned';
                }
                $emergency->order->save();
            }

            // 3) Синхронизируем статус
            $emergency->syncOrderStatus();

            Log::info('Helper assigned to roadside emergency', [
                'emergency_id' => $emergency->id,
                'helper_id' => $helper->id,
                'assigned_by' => auth()->id(),
            ]);

            DB::commit();

            return back()->with('status', 'Исполнитель назначен');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to assign helper', [
                'emergency_id' => $data['emergency_id'],
                'helper_id' => $data['helper_id'],
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Ошибка при назначении: '.$e->getMessage()]);
        }
    }

    /**
     * Assign partner to roadside emergency.
     */
    public function assignPartner(Request $request)
    {
        $data = $request->validate([
            'emergency_id' => ['required', 'exists:roadside_emergencies,id'],
            'partner_id' => ['required', 'exists:partners,id'],
        ]);

        try {
            DB::beginTransaction();

            $emergency = RoadsideEmergency::with('order')->findOrFail($data['emergency_id']);
            $partner = Partner::findOrFail($data['partner_id']);

            // Привязываем partner к RoadsideEmergency
            $emergency->resolved_by_partner_id = $partner->id;
            if ($emergency->status === 'new') {
                $emergency->status = 'assigned';
            }
            $emergency->save();

            // Если есть Order, обновляем roadside_partner_id
            if ($emergency->order) {
                $emergency->order->roadside_partner_id = $partner->id;
                $emergency->order->save();
            }

            Log::info('Partner assigned to roadside emergency', [
                'emergency_id' => $emergency->id,
                'partner_id' => $partner->id,
                'assigned_by' => auth()->id(),
            ]);

            DB::commit();

            return back()->with('status', 'Партнёр-эвакуатор назначен');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to assign partner', [
                'emergency_id' => $data['emergency_id'],
                'partner_id' => $data['partner_id'],
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Ошибка при назначении: '.$e->getMessage()]);
        }
    }
}
