<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['user', 'partner']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('position')) {
            $query->where('position', $request->position);
        }

        $employees = $query->get();

        return response()->json([
            'success' => true,
            'data' => $employees,
            'count' => $employees->count(),
            'message' => 'Employees retrieved successfully',
        ]);
    }

    public function show(int $id)
    {
        $employee = Employee::with(['user', 'partner'])->find($id);

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $employee,
            'message' => 'Employee retrieved successfully',
        ]);
    }
}
