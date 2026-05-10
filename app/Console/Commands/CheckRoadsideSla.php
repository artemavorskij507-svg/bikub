<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckRoadsideSla extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roadside:check-sla';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check roadside orders for SLA breaches and send notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking roadside orders for SLA breaches...');

        $maxPendingMinutes = config('roadside.sla.max_pending_minutes', 10);
        $maxAssignedMinutes = config('roadside.sla.max_assigned_minutes', 20);
        $notifyRoles = config('roadside.notify_roles', ['admin', 'operator', 'dispatcher']);

        $breachedOrders = collect();

        // Check pending orders
        $pendingBreached = Order::query()
            ->where('status', 'pending')
            ->where('created_at', '<', Carbon::now()->subMinutes($maxPendingMinutes))
            ->where(function ($q) {
                $q->whereHas('roadsideDetails')
                    ->orWhereHas('roadsideEmergency')
                    ->orWhereHas('vehicleInspection')
                    ->orWhereHas('orderItems.serviceType', function ($sq) {
                        $sq->where(function ($q) {
                            $q->whereIn('code', ['roadside_assistance', 'vehicle_inspection', 'vehicle_transport'])
                                ->orWhereIn('category', ['roadside_assistance', 'vehicle_inspection', 'vehicle_transport']);
                        });
                    });
            })
            ->get();

        foreach ($pendingBreached as $order) {
            $breachTime = $order->created_at->diffInMinutes(Carbon::now());
            $order->metadata = array_merge($order->metadata ?? [], [
                'sla' => [
                    'breached' => true,
                    'breach_type' => 'pending_timeout',
                    'breach_time' => $breachTime,
                    'breached_at' => Carbon::now()->toIso8601String(),
                ],
            ]);
            $order->save();
            $breachedOrders->push($order);

            $this->warn("Order #{$order->order_number} breached pending SLA ({$breachTime} min)");
        }

        // Check assigned orders (if assigned_at exists in metadata)
        $assignedBreached = Order::query()
            ->where('status', 'assigned')
            ->where(function ($q) {
                $q->whereHas('roadsideDetails')
                    ->orWhereHas('roadsideEmergency')
                    ->orWhereHas('vehicleInspection')
                    ->orWhereHas('orderItems.serviceType', function ($sq) {
                        $sq->where(function ($q) {
                            $q->whereIn('code', ['roadside_assistance', 'vehicle_inspection', 'vehicle_transport'])
                                ->orWhereIn('category', ['roadside_assistance', 'vehicle_inspection', 'vehicle_transport']);
                        });
                    });
            })
            ->get()
            ->filter(function ($order) use ($maxAssignedMinutes) {
                $assignedAt = $order->metadata['assigned_at'] ?? $order->updated_at;
                if (is_string($assignedAt)) {
                    $assignedAt = Carbon::parse($assignedAt);
                }

                return $assignedAt->diffInMinutes(Carbon::now()) > $maxAssignedMinutes;
            });

        foreach ($assignedBreached as $order) {
            $assignedAt = $order->metadata['assigned_at'] ?? $order->updated_at;
            if (is_string($assignedAt)) {
                $assignedAt = Carbon::parse($assignedAt);
            }
            $breachTime = $assignedAt->diffInMinutes(Carbon::now());

            $order->metadata = array_merge($order->metadata ?? [], [
                'sla' => [
                    'breached' => true,
                    'breach_type' => 'assigned_timeout',
                    'breach_time' => $breachTime,
                    'breached_at' => Carbon::now()->toIso8601String(),
                ],
            ]);
            $order->save();
            $breachedOrders->push($order);

            $this->warn("Order #{$order->order_number} breached assigned SLA ({$breachTime} min)");
        }

        // Send notifications to admins/operators/dispatchers
        if ($breachedOrders->isNotEmpty()) {
            $users = User::whereHas('roles', function ($q) use ($notifyRoles) {
                $q->whereIn('name', $notifyRoles);
            })->get();

            foreach ($users as $user) {
                foreach ($breachedOrders as $order) {
                    // Create database notification
                    $user->notify(new \App\Notifications\RoadsideSlaBreached($order));
                }
            }

            $this->info('Sent notifications to '.$users->count().' users about '.$breachedOrders->count().' breached orders');
        } else {
            $this->info('No SLA breaches found.');
        }

        return 0;
    }
}
