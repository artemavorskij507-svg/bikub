<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderAssignmentService;
use App\Services\Orders\OrderLifecycleService;
use App\Services\Payments\PaymentEngine;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Throwable;

class DispatchCenter extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Dispatch Center';
    protected static ?string $navigationGroup = 'Operations / Операции';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.dispatch-center';

    public array $stats = [];
    public string $filter = 'all';
    public array $workerSelection = [];
    public array $overrideReason = [];
    public array $internalNote = [];

    public function mount(): void
    {
        $this->reloadStats();
    }

    public function getOrdersProperty()
    {
        $query = Order::query()
            ->with(['user:id,name,email', 'assignedUser:id,name'])
            ->latest('id');

        $today = now()->startOfDay();
        match ($this->filter) {
            'waiting_dispatch' => $query->where('status', 'waiting_dispatch'),
            'unassigned' => $query->whereNull('assigned_to')->whereNotIn('status', ['completed', 'cancelled']),
            'active' => $query->whereIn('status', ['assigned', 'worker_accepted', 'worker_en_route', 'in_progress', 'arrived']),
            'urgent' => $query->where('priority', 'urgent'),
            'sla_risk' => $query->where('sla_breach_risk', true),
            'payment_problems' => $query->whereIn('payment_status', ['failed', 'cancelled', 'refunded']),
            'completed_today' => $query->where('status', 'completed')->where('completed_at', '>=', $today),
            'cancelled_disputed' => $query->whereIn('status', ['cancelled', 'disputed']),
            default => null,
        };

        return $query->limit(100)->get();
    }

    public function getWorkersProperty()
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['worker', 'courier']))
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function reloadStats(): void
    {
        $today = now()->startOfDay();
        $this->stats = [
            'active_orders' => Order::whereIn('status', ['assigned', 'worker_accepted', 'worker_en_route', 'in_progress', 'arrived'])->count(),
            'waiting_dispatch' => Order::where('status', 'waiting_dispatch')->count(),
            'unassigned' => Order::whereNull('assigned_to')->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'urgent' => Order::where('priority', 'urgent')->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'payment_problems' => Order::whereIn('payment_status', ['failed', 'cancelled', 'refunded'])->count(),
            'completed_today' => Order::where('status', 'completed')->where('completed_at', '>=', $today)->count(),
            'cancelled_disputed' => Order::whereIn('status', ['cancelled', 'disputed'])->count(),
        ];
    }

    public function assignWorker(int $orderId, int $workerId, OrderAssignmentService $assignments): void
    {
        $this->authorizeAction(['owner', 'admin', 'dispatcher']);
        try {
            $order = Order::findOrFail($orderId);
            $worker = User::findOrFail($workerId);
            $assignments->assign($order, $worker, auth()->id(), ['source' => 'dispatch_center']);
            $this->reloadStats();
            Notification::make()->title('Worker assigned')->success()->send();
        } catch (Throwable) {
            Notification::make()->title('Assign failed')->body('Unable to assign worker for this order.')->danger()->send();
        }
    }

    public function unassignWorker(int $orderId): void
    {
        $this->authorizeAction(['owner', 'admin', 'dispatcher']);
        try {
            $order = Order::findOrFail($orderId);
            $order->update(['assigned_to' => null]);
            $this->reloadStats();
            Notification::make()->title('Worker unassigned')->success()->send();
        } catch (Throwable) {
            Notification::make()->title('Unassign failed')->body('Unable to unassign worker for this order.')->danger()->send();
        }
    }

    public function changeStatus(int $orderId, string $status, OrderLifecycleService $lifecycle): void
    {
        $this->authorizeAction(['owner', 'admin', 'dispatcher', 'support']);
        try {
            $order = Order::findOrFail($orderId);
            $lifecycle->transition($order, $status, auth()->id(), ['source' => 'dispatch_center']);
            $this->reloadStats();
            Notification::make()->title('Status updated')->success()->send();
        } catch (Throwable) {
            Notification::make()->title('Status change failed')->body('Invalid lifecycle transition or access denied.')->danger()->send();
        }
    }

    public function overrideStatus(int $orderId, string $status, string $reason, OrderLifecycleService $lifecycle): void
    {
        $this->authorizeAction(['owner', 'admin', 'dispatcher']);
        try {
            $order = Order::findOrFail($orderId);
            $lifecycle->transition(
                $order,
                $status,
                auth()->id(),
                ['source' => 'dispatch_center', 'override_reason' => $reason],
                true
            );
            $this->reloadStats();
            Notification::make()->title('Status overridden')->warning()->send();
        } catch (Throwable) {
            Notification::make()->title('Override failed')->body('Unable to override status. Check reason and transition.')->danger()->send();
        }
    }

    public function manualPayment(int $orderId, string $action, PaymentEngine $payments): void
    {
        $this->authorizeAction(['owner', 'admin']);
        try {
            $order = Order::findOrFail($orderId);
            match ($action) {
                'reserve' => $payments->reserve($order),
                'capture' => $payments->capture($order),
                'refund' => $payments->refund($order),
                default => null,
            };
            $this->reloadStats();
            Notification::make()->title('Payment action applied')->success()->send();
        } catch (Throwable) {
            Notification::make()->title('Payment action failed')->body('Unable to apply payment action for this order.')->danger()->send();
        }
    }

    public function assignSelectedWorker(int $orderId, OrderAssignmentService $assignments): void
    {
        $workerId = (int) ($this->workerSelection[$orderId] ?? 0);
        if ($workerId <= 0) {
            $this->addError("workerSelection.$orderId", 'Select a worker before assignment.');
            Notification::make()->title('Worker is required')->body('Select worker first, then click assign.')->warning()->send();
            return;
        }

        $this->assignWorker($orderId, $workerId, $assignments);
    }

    public function applyOverride(int $orderId, string $status, OrderLifecycleService $lifecycle): void
    {
        $reason = trim((string) ($this->overrideReason[$orderId] ?? ''));
        if ($reason === '') {
            $this->addError("overrideReason.$orderId", 'Override reason is required.');
            Notification::make()->title('Override reason is required')->body('Enter reason before override status action.')->warning()->send();
            return;
        }

        $this->overrideStatus($orderId, $status, $reason, $lifecycle);
    }

    public function saveInternalNote(int $orderId): void
    {
        $this->authorizeAction(['owner', 'admin', 'dispatcher', 'support']);

        $note = trim((string) ($this->internalNote[$orderId] ?? ''));
        if ($note === '') {
            Notification::make()->title('Internal note is required')->body('Type internal note before saving.')->warning()->send();
            return;
        }

        $order = Order::findOrFail($orderId);
        $metadata = (array) ($order->metadata ?? []);
        $metadata['internal_notes'][] = [
            'note' => $note,
            'author_id' => auth()->id(),
            'created_at' => now()->toIso8601String(),
        ];
        $order->metadata = $metadata;
        $order->save();
        $this->internalNote[$orderId] = '';

        Notification::make()->title('Internal note saved')->success()->send();
    }

    private function authorizeAction(array $roles): void
    {
        $user = auth()->user();
        abort_if(! $user, 403);
        if (method_exists($user, 'hasAnyRole')) {
            abort_unless($user->hasAnyRole($roles), 403);
        }
    }
}
