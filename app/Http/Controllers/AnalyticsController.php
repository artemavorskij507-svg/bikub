<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function statistics(Request $request)
    {
        $period = $request->get('period', '30'); // days
        $startDate = Carbon::now()->subDays($period);

        $stats = [
            'orders' => [
                'total' => Order::count(),
                'period' => Order::where('created_at', '>=', $startDate)->count(),
                'today' => Order::whereDate('created_at', today())->count(),
                'pending' => Order::where('status', 'pending')->count(),
                'completed' => Order::where('status', 'completed')->count(),
                'cancelled' => Order::where('status', 'cancelled')->count(),
            ],

            'revenue' => [
                'total' => Order::where('payment_status', 'paid')->sum('total_amount'),
                'period' => Order::where('payment_status', 'paid')
                    ->where('created_at', '>=', $startDate)
                    ->sum('total_amount'),
                'today' => Order::where('payment_status', 'paid')
                    ->whereDate('created_at', today())
                    ->sum('total_amount'),
                'average_order_value' => Order::where('payment_status', 'paid')
                    ->avg('total_amount'),
            ],

            'services' => [
                'total_categories' => ServiceCategory::count(),
                'total_types' => ServiceType::count(),
                'active_types' => ServiceType::where('is_active', true)->count(),
                'popular_category' => $this->getPopularCategory($startDate),
            ],

            'tasks' => [
                'total' => Task::count(),
                'queued' => Task::where('status', 'queued')->count(),
                'in_progress' => Task::where('status', 'enroute')->count(),
                'completed' => Task::where('status', 'done')->count(),
                'average_completion_time' => $this->getAverageCompletionTime(),
            ],

            'users' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'new_period' => User::where('created_at', '>=', $startDate)->count(),
            ],

            'payment_metrics' => [
                'success_rate' => $this->getPaymentSuccessRate($startDate),
                'refund_rate' => $this->getRefundRate($startDate),
                'average_payment_time' => $this->getAveragePaymentTime($startDate),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'period' => $period.' days',
            'generated_at' => now(),
        ]);
    }

    /**
     * Get orders report with filters.
     */
    public function ordersReport(Request $request)
    {
        $query = Order::with(['user', 'orderItems.serviceType', 'scheduleSlot']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('service_category')) {
            $query->whereHas('orderItems.serviceType.serviceCategory', function ($q) use ($request) {
                $q->where('code', $request->service_category);
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(50);

        // Add calculated fields
        $orders->getCollection()->transform(function ($order) {
            $order->completion_time = $this->calculateCompletionTime($order);
            $order->service_categories = $order->orderItems->pluck('serviceType.serviceCategory.name')->unique();

            return $order;
        });

        return response()->json([
            'success' => true,
            'data' => $orders,
            'filters_applied' => $request->all(),
        ]);
    }

    /**
     * Export data to CSV.
     */
    public function export(Request $request)
    {
        $type = $request->get('type', 'orders');
        $format = $request->get('format', 'csv');

        switch ($type) {
            case 'orders':
                return $this->exportOrders($request, $format);
            case 'services':
                return $this->exportServices($format);
            case 'tasks':
                return $this->exportTasks($request, $format);
            default:
                return response()->json(['error' => 'Invalid export type'], 400);
        }
    }

    /**
     * Export orders to CSV.
     */
    private function exportOrders(Request $request, string $format)
    {
        $query = Order::with(['user', 'orderItems.serviceType']);

        // Apply same filters as ordersReport
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        $csvData = [];
        $csvData[] = [
            'Order Number',
            'Customer Name',
            'Customer Email',
            'Status',
            'Payment Status',
            'Total Amount',
            'Currency',
            'Service Types',
            'Created At',
            'Completed At',
        ];

        foreach ($orders as $order) {
            $csvData[] = [
                $order->order_number,
                $order->user->name ?? 'N/A',
                $order->user->email ?? 'N/A',
                $order->status,
                $order->payment_status,
                $order->total_amount,
                $order->currency,
                $order->orderItems->pluck('serviceType.name')->join(', '),
                $order->created_at->format('Y-m-d H:i:s'),
                $order->completed_at?->format('Y-m-d H:i:s') ?? 'N/A',
            ];
        }

        $filename = 'orders_export_'.now()->format('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Export services to CSV.
     */
    private function exportServices(string $format)
    {
        $services = ServiceType::with('serviceCategory')->get();

        $csvData = [];
        $csvData[] = [
            'Code',
            'Name',
            'Category',
            'Description',
            'Base Price',
            'Skills',
            'Inventory',
            'Active',
        ];

        foreach ($services as $service) {
            $csvData[] = [
                $service->code,
                $service->name,
                $service->serviceCategory->name ?? 'N/A',
                $service->description,
                $service->default_pricing['base'] ?? 'N/A',
                is_array($service->skills) ? implode(', ', $service->skills) : 'N/A',
                is_array($service->inventory) ? implode(', ', $service->inventory) : 'N/A',
                $service->is_active ? 'Yes' : 'No',
            ];
        }

        $filename = 'services_export_'.now()->format('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Export tasks to CSV.
     */
    private function exportTasks(Request $request, string $format)
    {
        $query = Task::with(['orderItem.order', 'assignee']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $tasks = $query->orderBy('created_at', 'desc')->get();

        $csvData = [];
        $csvData[] = [
            'Task ID',
            'Order Number',
            'Assignee',
            'Status',
            'Priority',
            'Created At',
            'Started At',
            'Completed At',
        ];

        foreach ($tasks as $task) {
            $csvData[] = [
                $task->id,
                $task->orderItem->order->order_number ?? 'N/A',
                $task->assignee->name ?? 'Unassigned',
                $task->status,
                $task->priority_label,
                $task->created_at->format('Y-m-d H:i:s'),
                $task->started_at?->format('Y-m-d H:i:s') ?? 'N/A',
                $task->completed_at?->format('Y-m-d H:i:s') ?? 'N/A',
            ];
        }

        $filename = 'tasks_export_'.now()->format('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Helper methods for statistics.
     */
    private function getPopularCategory($startDate)
    {
        $category = ServiceCategory::withCount([
            'serviceTypes' => function ($query) use ($startDate) {
                $query->whereHas('orderItems.order', function ($q) use ($startDate) {
                    $q->where('created_at', '>=', $startDate);
                });
            },
        ])->orderBy('service_types_count', 'desc')->first();

        return $category ? $category->name : 'N/A';
    }

    private function getAverageCompletionTime()
    {
        $completedTasks = Task::whereNotNull('completed_at')
            ->whereNotNull('started_at')
            ->get();

        if ($completedTasks->isEmpty()) {
            return 0;
        }

        $totalMinutes = $completedTasks->sum(function ($task) {
            return $task->started_at->diffInMinutes($task->completed_at);
        });

        return round($totalMinutes / $completedTasks->count(), 2);
    }

    private function getPaymentSuccessRate($startDate)
    {
        $totalPayments = Order::where('created_at', '>=', $startDate)->count();
        $successfulPayments = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->count();

        return $totalPayments > 0 ? round(($successfulPayments / $totalPayments) * 100, 2) : 0;
    }

    private function getRefundRate($startDate)
    {
        $paidOrders = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->count();

        $refundedOrders = Order::where('payment_status', 'refunded')
            ->where('created_at', '>=', $startDate)
            ->count();

        return $paidOrders > 0 ? round(($refundedOrders / $paidOrders) * 100, 2) : 0;
    }

    private function getAveragePaymentTime($startDate)
    {
        // This would require tracking payment intent creation time
        // For now, return a placeholder
        return 0;
    }

    private function calculateCompletionTime($order)
    {
        if (! $order->started_at || ! $order->completed_at) {
            return null;
        }

        return $order->started_at->diffInMinutes($order->completed_at);
    }
}
