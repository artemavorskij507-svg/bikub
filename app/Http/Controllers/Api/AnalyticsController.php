<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Order;
use App\Models\Partner;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Get overall statistics
     */
    public function statistics(Request $request)
    {
        $stats = [
            'orders' => [
                'total' => Order::count(),
                'completed' => Order::where('status', 'completed')->count(),
                'in_progress' => Order::where('status', 'in_progress')->count(),
                'pending' => Order::where('status', 'pending')->count(),
                'revenue' => Order::where('status', 'completed')->sum('total_amount'),
            ],
            'services' => [
                'total' => ServiceType::count(),
                'active' => ServiceType::where('is_active', true)->count(),
                'by_category' => ServiceType::selectRaw('category, COUNT(*) as count')
                    ->groupBy('category')
                    ->get(),
            ],
            'users' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
            ],
            'partners' => [
                'total' => Partner::count(),
                'active' => Partner::where('is_active', true)->count(),
            ],
            'employees' => [
                'total' => Employee::count(),
                'active' => Employee::where('status', 'active')->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Statistics retrieved successfully',
        ]);
    }

    /**
     * Get orders report
     */
    public function ordersReport(Request $request)
    {
        $startDate = $request->input('start_date', now()->subMonth());
        $endDate = $request->input('end_date', now());

        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->with(['user', 'orderItems.serviceType'])
            ->get();

        $report = [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'summary' => [
                'total_orders' => $orders->count(),
                'total_revenue' => $orders->sum('total_amount'),
                'average_order_value' => $orders->avg('total_amount'),
            ],
            'by_status' => $orders->groupBy('status')->map->count(),
            'orders' => $orders,
        ];

        return response()->json([
            'success' => true,
            'data' => $report,
            'message' => 'Orders report generated successfully',
        ]);
    }

    /**
     * Export data to CSV
     */
    public function export(Request $request)
    {
        $type = $request->input('type', 'orders');

        switch ($type) {
            case 'orders':
                $data = Order::with(['user', 'orderItems'])->get();
                break;
            case 'users':
                $data = User::all();
                break;
            case 'partners':
                $data = Partner::all();
                break;
            case 'employees':
                $data = Employee::with(['user', 'partner'])->get();
                break;
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid export type',
                ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => $data->count(),
            'message' => 'Data exported successfully',
        ]);
    }
}
