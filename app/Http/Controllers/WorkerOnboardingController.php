<?php

namespace App\Http\Controllers;

use App\Models\WorkerApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkerOnboardingController extends Controller
{
    public function create(): View
    {
        return view('public.become-worker');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:60'],
            'city' => ['nullable', 'string', 'max:120'],
            'role_requested' => ['nullable', 'string', 'max:120'],
            'has_car' => ['nullable', 'boolean'],
            'vehicle_type' => ['nullable', 'string', 'max:120'],
            'license_info' => ['nullable', 'string', 'max:255'],
            'languages' => ['nullable', 'string'],
            'experience' => ['nullable', 'string'],
            'availability' => ['nullable', 'string'],
            'work_zones' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        WorkerApplication::create([
            ...$data,
            'languages' => empty($data['languages']) ? null : array_map('trim', explode(',', $data['languages'])),
            'work_zones' => empty($data['work_zones']) ? null : array_map('trim', explode(',', $data['work_zones'])),
            'status' => 'new_application',
        ]);

        return back()->with('status', 'Application submitted');
    }
}

