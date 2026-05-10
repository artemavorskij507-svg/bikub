<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoadsideEmergency extends Model
{
    use HasFactory;

    /**
     * Status constants.
     */
    public const STATUS_NEW = 'new';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_ON_ROUTE = 'on_route';

    public const STATUS_ON_SPOT = 'on_spot';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_FAILED = 'failed';

    /**
     * Active statuses for dispatcher board.
     */
    protected const ACTIVE_STATUSES = [
        self::STATUS_NEW,
        self::STATUS_ASSIGNED,
        self::STATUS_ON_ROUTE,
        self::STATUS_IN_PROGRESS,
    ];

    protected $fillable = [
        'customer_id',
        'road_helper_id',
        'resolved_by_partner_id',
        'order_id',
        'incident_type',
        'incident_description',
        'photos',
        'lat',
        'lng',
        'status',
        'metadata',
        'tracking_token',
        'tracking_url',
        'customer_notified_at',
    ];

    protected $casts = [
        'photos' => 'array',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'metadata' => 'array',
        'customer_notified_at' => 'datetime',
    ];

    /**
     * Validation rules.
     */
    public static function rules(): array
    {
        return [
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * Set latitude with validation.
     */
    public function setLatAttribute($value): void
    {
        if ($value !== null) {
            $value = (float) $value;
            if ($value < -90 || $value > 90) {
                throw new \InvalidArgumentException("Latitude must be between -90 and 90, got: {$value}");
            }
            $this->attributes['lat'] = $value;
        } else {
            $this->attributes['lat'] = null;
        }
    }

    /**
     * Set longitude with validation.
     */
    public function setLngAttribute($value): void
    {
        if ($value !== null) {
            $value = (float) $value;
            if ($value < -180 || $value > 180) {
                throw new \InvalidArgumentException("Longitude must be between -180 and 180, got: {$value}");
            }
            $this->attributes['lng'] = $value;
        } else {
            $this->attributes['lng'] = null;
        }
    }

    /**
     * Get the customer (user) who reported the emergency.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the road helper assigned to this emergency.
     */
    public function helper(): BelongsTo
    {
        return $this->belongsTo(RoadHelperProfile::class, 'road_helper_id');
    }

    /**
     * Get the partner who resolved this emergency.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'resolved_by_partner_id');
    }

    /**
     * Get the order associated with this emergency.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the assigned executor (from order).
     */
    public function assignedExecutor()
    {
        return $this->hasOneThrough(
            \App\Models\User::class,
            Order::class,
            'id', // Foreign key on orders table
            'id', // Foreign key on users table
            'order_id', // Local key on roadside_emergencies table
            'assigned_to' // Local key on orders table
        );
    }

    /**
     * Get the assigned partner.
     */
    public function assignedPartner()
    {
        return $this->belongsTo(Partner::class, 'resolved_by_partner_id');
    }

    /**
     * Build timeline of status changes.
     */
    public function buildTimeline(): array
    {
        $order = $this->order;

        return [
            [
                'key' => 'created',
                'label' => 'Заявка создана',
                'completed' => (bool) $this->created_at,
                'at' => $this->created_at,
            ],
            [
                'key' => 'accepted',
                'label' => 'Принята диспетчером',
                'completed' => in_array($this->status, ['assigned', 'on_route', 'in_progress', 'completed', 'failed', 'cancelled']),
                'at' => $this->metadata['accepted_at'] ?? ($order?->created_at ?? null),
            ],
            [
                'key' => 'assigned',
                'label' => 'Назначен исполнитель/эвакуатор',
                'completed' => in_array($this->status, ['assigned', 'on_route', 'in_progress', 'completed', 'failed', 'cancelled']),
                'at' => $this->metadata['assigned_at'] ?? ($order?->started_at ?? null),
            ],
            [
                'key' => 'en_route',
                'label' => 'Специалист в пути',
                'completed' => in_array($this->status, ['on_route', 'in_progress', 'completed', 'failed', 'cancelled']),
                'at' => $this->metadata['en_route_at'] ?? null,
            ],
            [
                'key' => 'on_site',
                'label' => 'Специалист на месте',
                'completed' => in_array($this->status, ['in_progress', 'completed', 'failed', 'cancelled']),
                'at' => $this->metadata['on_site_at'] ?? null,
            ],
            [
                'key' => 'completed',
                'label' => 'Работа завершена',
                'completed' => $this->status === 'completed',
                'at' => $this->metadata['completed_at'] ?? ($order?->completed_at ?? null),
            ],
        ];
    }

    /**
     * Get current status description.
     */
    public function getCurrentStatusDescription(): string
    {
        return match ($this->status) {
            'new' => 'Диспетчер обрабатывает ваш запрос',
            'assigned' => 'Специалист назначен и скоро направится к вам',
            'on_route' => 'Специалист направляется к вам',
            'in_progress' => 'Специалист на месте, работа выполняется',
            'completed' => 'Работа успешно завершена. Спасибо за использование нашего сервиса!',
            'failed' => 'К сожалению, не удалось выполнить запрос',
            'cancelled' => 'Запрос отменён',
            default => 'Обработка запроса',
        };
    }

    /**
     * Generate tracking token and URL.
     */
    public function generateTrackingToken(): void
    {
        if (empty($this->tracking_token)) {
            $this->tracking_token = \Illuminate\Support\Str::uuid()->toString();
            $this->tracking_url = route('public.roadside.track', $this->tracking_token);
            $this->save();
        }
    }

    /**
     * Sync Order status based on RoadsideEmergency status.
     */
    public function syncOrderStatus(): void
    {
        if (! $this->order) {
            return;
        }

        $status = $this->status;

        // Маппинг RoadsideEmergency → Order
        $orderStatus = match ($status) {
            'new', 'assigned' => 'assigned',
            'on_route' => 'in_progress',
            'in_progress' => 'in_progress',
            'completed' => 'completed',
            'failed', 'cancelled' => 'cancelled',
            default => $this->order->status,
        };

        if ($orderStatus !== $this->order->status) {
            $this->order->status = $orderStatus;

            // Оновлюємо timestamps
            if ($orderStatus === 'in_progress' && ! $this->order->started_at) {
                $this->order->started_at = now();
            }
            if ($orderStatus === 'completed' && ! $this->order->completed_at) {
                $this->order->completed_at = now();
            }

            $this->order->save();
        }
    }

    /**
     * Update status and sync with Order.
     */
    public function updateStatus(string $status, ?array $metadata = null): void
    {
        $this->status = $status;

        if ($metadata) {
            $currentMetadata = $this->metadata ?? [];
            $this->metadata = array_merge($currentMetadata, $metadata);
        }

        $this->save();
        $this->syncOrderStatus();
    }

    /**
     * Scope for active emergencies (for dispatcher board).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    /**
     * Scope for new emergencies.
     */
    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    /**
     * Scope for awaiting assignment.
     */
    public function scopeAwaitingAssignment($query)
    {
        return $query->where('status', self::STATUS_NEW)
            ->whereNull('road_helper_id')
            ->whereNull('resolved_by_partner_id');
    }

    /**
     * Status helper methods.
     */
    public function isNew(): bool
    {
        return $this->status === self::STATUS_NEW;
    }

    public function isAssigned(): bool
    {
        return $this->status === self::STATUS_ASSIGNED;
    }

    public function isEnRoute(): bool
    {
        return $this->status === self::STATUS_ON_ROUTE;
    }

    public function isOnSpot(): bool
    {
        return $this->status === self::STATUS_ON_SPOT;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isDone(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_REJECTED,
            self::STATUS_FAILED,
        ], true);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Get executor user ID (from order or helper).
     */
    public function getAssignedUserId(): ?int
    {
        // Сначала проверяем через Order
        if ($this->order && $this->order->assigned_to) {
            return $this->order->assigned_to;
        }

        // Затем через helper
        if ($this->helper && $this->helper->user_id) {
            return $this->helper->user_id;
        }

        return null;
    }

    /**
     * Get executor payout amount.
     */
    public function getExecutorPayout(): float
    {
        // Сначала из metadata
        $payout = $this->metadata['executor_payout'] ?? null;

        if ($payout !== null) {
            return (float) $payout;
        }

        // Если нет, можно вычислить из Order->total_amount (временная формула)
        if ($this->order && $this->order->total_amount) {
            // TODO: заменить на реальный сервис прайсинга
            return max(0, (float) $this->order->total_amount * 0.6); // временно 60% исполнителю
        }

        return 0;
    }

    /**
     * Sync executor payout to Order metadata.
     */
    public function syncExecutorPayoutToOrder(): void
    {
        if (! $this->order) {
            return;
        }

        $executorPayout = $this->getExecutorPayout();

        $meta = $this->order->metadata ?? [];
        $meta['executor_payout'] = $executorPayout;
        $this->order->metadata = $meta;
        $this->order->save();
    }

    /**
     * SLA indicators.
     */
    public function getIsOverdueAssignmentAttribute(): bool
    {
        // Если новая, без назначенного исполнителя, старше 10 минут
        return $this->created_at
            && $this->status === self::STATUS_NEW
            && ! $this->road_helper_id
            && ! $this->resolved_by_partner_id
            && $this->created_at->lt(now()->subMinutes(10));
    }

    public function getIsOverdueArrivalAttribute(): bool
    {
        // Если "в пути", старше 30 минут
        $enRouteAt = $this->metadata['en_route_at'] ?? null;

        if (! $enRouteAt) {
            return false;
        }

        if (is_string($enRouteAt)) {
            $enRouteAt = \Carbon\Carbon::parse($enRouteAt);
        }

        return $this->status === self::STATUS_ON_ROUTE
            && $enRouteAt->lt(now()->subMinutes(30));
    }
}
