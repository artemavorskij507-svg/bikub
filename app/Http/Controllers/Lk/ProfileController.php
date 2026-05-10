<?php

namespace App\Http\Controllers\Lk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $employee = $user->employee()->with(['currentZone'])->first();

        $preferences = $user->preferences ?? [];

        return view('lk.profile', [
            'user' => $user,
            'employee' => $employee,
            'preferences' => $preferences,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'vehicle_type' => ['nullable', 'string', 'max:100'],
            'vehicle_plate' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $data) {
            // name
            $user->name = $data['name'];

            // phone → либо колонка, либо preferences
            $preferences = $user->preferences ?? [];
            if (Schema::hasColumn($user->getTable(), 'phone')) {
                $user->phone = $data['phone'] ?? $user->phone;
            } else {
                $preferences['phone'] = $data['phone'] ?? ($preferences['phone'] ?? null);
            }

            // employee
            $employee = $user->employee;
            if ($employee) {
                if (Schema::hasColumn($employee->getTable(), 'vehicle_type')) {
                    $employee->vehicle_type = $data['vehicle_type'] ?? $employee->vehicle_type;
                } else {
                    $preferences['vehicle_type'] = $data['vehicle_type'] ?? ($preferences['vehicle_type'] ?? null);
                }

                if (Schema::hasColumn($employee->getTable(), 'vehicle_plate')) {
                    $employee->vehicle_plate = $data['vehicle_plate'] ?? $employee->vehicle_plate;
                } else {
                    $preferences['vehicle_plate'] = $data['vehicle_plate'] ?? ($preferences['vehicle_plate'] ?? null);
                }

                if (Schema::hasColumn($employee->getTable(), 'notes')) {
                    $employee->notes = $data['notes'] ?? $employee->notes;
                } else {
                    $preferences['worker_notes'] = $data['notes'] ?? ($preferences['worker_notes'] ?? null);
                }

                $employee->save();
            } else {
                // Если employee нет — всё кладём в preferences
                $preferences['vehicle_type'] = $data['vehicle_type'] ?? ($preferences['vehicle_type'] ?? null);
                $preferences['vehicle_plate'] = $data['vehicle_plate'] ?? ($preferences['vehicle_plate'] ?? null);
                $preferences['worker_notes'] = $data['notes'] ?? ($preferences['worker_notes'] ?? null);
            }

            $user->preferences = $preferences;
            $user->save();
        });

        return redirect()
            ->route('lk.profile')
            ->with('status', 'Профиль успешно обновлён.');
    }
}
