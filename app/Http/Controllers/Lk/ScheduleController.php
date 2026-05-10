<?php

namespace App\Http\Controllers\Lk;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $today = now()->startOfDay();

        // Отримуємо Employee для користувача
        $employee = $user->employee;

        // Якщо немає Employee, показуємо порожній список
        if (! $employee) {
            return view('lk.schedule', [
                'user' => $user,
                'today' => $today,
                'todayShifts' => collect(),
                'upcomingShifts' => collect(),
                'pastShifts' => collect(),
                'availability' => $this->getAvailability($user),
            ]);
        }

        // Отримуємо смены через ScheduleSlot та schedule_slot_employees
        $hasScheduleSchema = Schema::hasTable('schedule_slots')
            && Schema::hasTable('schedule_slot_employees')
            && Schema::hasTable('employees');

        if (! $hasScheduleSchema) {
            return view('lk.schedule', [
                'user' => $user,
                'today' => $today,
                'todayShifts' => collect(),
                'upcomingShifts' => collect(),
                'pastShifts' => collect(),
                'availability' => $this->getAvailability($user),
            ]);
        }
        $upcomingShifts = ScheduleSlot::whereHas('employees', function ($query) use ($employee) {
            $query->where('employees.id', $employee->id);
        })
            ->where('start_at', '>=', $today)
            ->with(['zone', 'serviceType', 'employees'])
            ->orderBy('start_at', 'asc')
            ->limit(20)
            ->get();

        $pastShifts = ScheduleSlot::whereHas('employees', function ($query) use ($employee) {
            $query->where('employees.id', $employee->id);
        })
            ->where('end_at', '<', $today)
            ->with(['zone', 'serviceType', 'employees'])
            ->orderBy('end_at', 'desc')
            ->limit(20)
            ->get();

        // Смены, які перетинають сьогоднішній день
        $todayShifts = ScheduleSlot::whereHas('employees', function ($query) use ($employee) {
            $query->where('employees.id', $employee->id);
        })
            ->where(function ($query) use ($today) {
                $query->where(function ($q) use ($today) {
                    // Смена починається сьогодні
                    $q->whereDate('start_at', $today->toDateString());
                })
                    ->orWhere(function ($q) use ($today) {
                        // Смена закінчується сьогодні
                        $q->whereDate('end_at', $today->toDateString());
                    })
                    ->orWhere(function ($q) use ($today) {
                        // Смена охоплює весь день
                        $q->where('start_at', '<=', $today->copy()->endOfDay())
                            ->where('end_at', '>=', $today->copy()->startOfDay());
                    });
            })
            ->with(['zone', 'serviceType', 'employees', 'orders'])
            ->orderBy('start_at', 'asc')
            ->get();

        return view('lk.schedule', [
            'user' => $user,
            'today' => $today,
            'todayShifts' => $todayShifts,
            'upcomingShifts' => $upcomingShifts,
            'pastShifts' => $pastShifts,
            'availability' => $this->getAvailability($user),
        ]);
    }

    /**
     * Update worker availability.
     */
    public function updateAvailability(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validate([
            'today' => ['nullable', 'boolean'],
            'tomorrow' => ['nullable', 'boolean'],
        ]);

        // Зберігаємо availability в preferences користувача
        $preferences = $user->preferences ?? [];
        if (isset($validated['today'])) {
            $preferences['availability_today'] = (bool) $validated['today'];
        }
        if (isset($validated['tomorrow'])) {
            $preferences['availability_tomorrow'] = (bool) $validated['tomorrow'];
        }

        $user->preferences = $preferences;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Доступність оновлено',
            'availability' => $this->getAvailability($user),
        ]);
    }

    /**
     * Get availability flags for user.
     */
    private function getAvailability($user): array
    {
        $preferences = $user->preferences ?? [];

        return [
            'today' => $preferences['availability_today'] ?? false,
            'tomorrow' => $preferences['availability_tomorrow'] ?? false,
        ];
    }
}

