<?php

namespace App\Models;

use App\Enums\PaymentFlow;
use App\Enums\ServiceType;
use App\Events\OrderPlaced;
use App\Models\Delivery\DeliveryOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Traits\LogsActivity;

class Order extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            // Auto-generate order_number if not set
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });

        static::created(function ($order) {
            try {
                event(new OrderPlaced($order));
            } catch (\Exception $e) {
                \Log::error('Failed to dispatch OrderPlaced event: '.$e->getMessage(), [
                    'order_id' => $order->id,
                    'exception' => $e,
                ]);
            }
        });
    }

    protected $fillable = [
        'order_number',
        'user_id',
        'store_id',
        'service_type',
        'assigned_to',
        'parent_order_id',
        'roadside_partner_id',
        'status',
        'priority',
        'notes',
        'cancellation_reason',
        'location',
        'scheduled_at',
        'schedule_slot_id',
        'started_at',
        'completed_at',
        'total_amount',
        'currency',
        'payment_status',
        'payment_flow',
        'payment_method',
        'payment_intent_id',
        'receipt_url',
        'estimated_total',
        'buffer_total',
        'actual_total',
        'final_price',
        'discount_amount',
        'coupon_code',
        'points_to_redeem',
        'metadata',
        'roadside_partner_metadata',
        'address_id',
        'geo_zone_id',
        'sla_policy_id',
        'sla_deadline',
        'sla_breach_risk',
        'weather_conditions',
    ];

    protected $casts = [
        'location' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'metadata' => 'array',
        'roadside_partner_metadata' => 'array',
        'sla_deadline' => 'datetime',
        'sla_breach_risk' => 'boolean',
        'weather_conditions' => 'array',
        'delivery_start_time' => 'datetime',
        'delivery_end_time' => 'datetime',
        'estimated_total' => 'integer',
        'buffer_total' => 'integer',
        'actual_total' => 'integer',
        'points_to_redeem' => 'integer',
        'payment_flow' => PaymentFlow::class,
    ];

    // Activity logging configuration
    protected static $logAttributes = [
        'status',
        'priority',
        'assigned_to',
        'scheduled_at',
        'started_at',
        'completed_at',
        'total_amount',
        'payment_status',
        'cancellation_reason',
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    protected static $logName = 'order';

    /**
     * Get the activity log options for the model.
     */
    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logOnly(static::$logAttributes)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName(static::$logName);
    }

    /**
     * Get the schedule slot for this order.
     */
    public function scheduleSlot()
    {
        return $this->belongsTo(ScheduleSlot::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the tasks for this order.
     */
    public function tasks()
    {
        return $this->hasMany(Task::class, 'order_id');
    }

    public function events()
    {
        return $this->hasMany(OrderEvent::class)->latest('id');
    }

    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user assigned to this order.
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the order items for this order.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the address for this order.
     */
    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * Get the geo zone for this order.
     */
    public function geoZone()
    {
        return $this->belongsTo(GeoZone::class);
    }

    public function deliveryOrder()
    {
        return $this->hasOne(DeliveryOrder::class);
    }

    /**
     * Get the SLA policy for this order.
     */
    public function slaPolicy()
    {
        return $this->belongsTo(SlaPolicy::class);
    }

    /**
     * Get the roadside assistance details for this order.
     */
    public function roadsideDetails()
    {
        return $this->hasOne(RoadsideAssistanceDetail::class);
    }

    /**
     * Get the roadside emergency for this order.
     */
    public function roadsideEmergency()
    {
        return $this->hasOne(RoadsideEmergency::class);
    }

    /**
     * Get the vehicle inspection request for this order.
     */
    public function vehicleInspection()
    {
        return $this->hasOne(VehicleInspectionRequest::class);
    }

    /**
     * Eco-disposal order details.
     */
    public function disposalDetails()
    {
        return $this->hasOne(DisposalOrderDetails::class, 'order_id');
    }

    /**
     * Eco certificate relation.
     */
    public function ecoCertificate()
    {
        return $this->hasOne(EcoCertificate::class, 'order_id');
    }

    /**
     * Social care order details.
     */
    public function careDetails()
    {
        return $this->hasOne(CareOrderDetails::class, 'order_id');
    }

    /**
     * Errand order details.
     */
    public function errandDetails()
    {
        return $this->hasOne(ErrandOrderDetails::class, 'order_id');
    }

    /**
     * Moving order details.
     */
    public function movingOrder()
    {
        return $this->hasOne(\App\Models\Moving\MovingOrder::class);
    }

    public function careChangeRequests(): HasMany
    {
        return $this->hasMany(CareOrderChangeRequest::class);
    }

    /**
     * Handyman order details.
     */
    public function handymanDetails()
    {
        return $this->hasOne(HandymanOrderDetails::class);
    }

    /**
     * Handyman assignments.
     */
    public function handymanAssignments()
    {
        return $this->hasMany(HandymanAssignment::class);
    }

    /**
     * Primary handyman assignment.
     */
    public function primaryHandymanAssignment()
    {
        return $this->hasOne(HandymanAssignment::class)->where('is_primary', true);
    }

    /**
     * Get the parent order for this order.
     */
    public function parentOrder()
    {
        return $this->belongsTo(self::class, 'parent_order_id');
    }

    /**
     * Get the sub-orders (child orders) for this order.
     */
    public function subOrders()
    {
        return $this->hasMany(self::class, 'parent_order_id');
    }

    /**
     * Create a sub-order for this order.
     */
    public function createSubOrder(array $attributes = []): self
    {
        $attributes['parent_order_id'] = $this->id;

        // Р СњР В°РЎРѓР В»Р ВµР Т‘РЎС“Р ВµР С Р С—Р С•Р В»РЎРЉР В·Р С•Р Р†Р В°РЎвЂљР ВµР В»РЎРЏ/Р С”Р В»Р С‘Р ВµР Р…РЎвЂљР В° Р С—Р С• РЎС“Р СР С•Р В»РЎвЂЎР В°Р Р…Р С‘РЎР‹
        if (! isset($attributes['user_id'])) {
            $attributes['user_id'] = $this->user_id;
        }

        return static::create($attributes);
    }

    /**
     * Get roadside sub-orders for this order.
     */
    public function roadsideSubOrders()
    {
        return $this->subOrders()->where(function ($q) {
            $q->whereHas('roadsideDetails')
                ->orWhereHas('roadsideEmergency')
                ->orWhereHas('vehicleInspection')
                ->orWhereHas('orderItems.serviceType', function ($sq) {
                    $sq->where(function ($q) {
                        $q->whereIn('code', ['roadside_assistance', 'vehicle_inspection', 'vehicle_transport'])
                            ->orWhereIn('category', ['roadside_assistance', 'vehicle_inspection', 'vehicle_transport']);
                    });
                });
        });
    }

    /**
     * Eco-disposal sub-orders for this order.
     */
    public function ecoSubOrders()
    {
        return $this->subOrders()->where(function ($q) {
            $q->where('metadata->service_type', 'eco_disposal');
        });
    }

    /**
     * Social Care sub-orders for this order.
     */
    public function socialCareSubOrders()
    {
        return $this->subOrders()->where(function ($q) {
            $q->whereHas('careDetails')
                ->orWhere('metadata->service_type', 'social_care_visit');
        });
    }

    public function repairProject()
    {
        return $this->hasOne(RepairProject::class);
    }

    public function handymanMaterials()
    {
        return $this->hasMany(HandymanMaterialsEntry::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    public function workWarranties()
    {
        return $this->hasMany(WorkWarranty::class);
    }

    public function review()
    {
        return $this->hasOne(OrderReview::class);
    }

    /**
     * Get the care context for this order (if it's for a vulnerable client).
     */
    public function careContext()
    {
        return $this->hasOne(OrderCareContext::class);
    }

    public function isRootOrder(): bool
    {
        return $this->parent_order_id === null;
    }

    public function hasSubOrders(): bool
    {
        return $this->subOrders()->exists();
    }

    /**
     * Get the roadside partner for this order.
     */
    public function roadsidePartner()
    {
        return $this->belongsTo(Partner::class, 'roadside_partner_id');
    }

    /**
     * Check if this order is a roadside assistance order.
     */
    public function isRoadside(): bool
    {
        if ($this->service_type && in_array($this->service_type, [
            ServiceType::ROAD_ASSIST->value,
            ServiceType::VEHICLE_TOW->value,
            ServiceType::INSPECTION_BASIC->value,
            ServiceType::INSPECTION_FULL->value,
            ServiceType::INSPECTION_SERVICE->value,
        ], true)) {
            return true;
        }

        $roadsideServiceTypes = ['roadside_assistance', 'vehicle_inspection', 'vehicle_transport'];

        if (Schema::hasTable('roadside_assistance_details')) {
            if ($this->relationLoaded('roadsideDetails')) {
                return $this->roadsideDetails !== null;
            }

            if ($this->roadsideDetails()->exists()) {
                return true;
            }
        }

        if ($this->relationLoaded('orderItems')) {
            foreach ($this->orderItems as $item) {
                if ($item->serviceType) {
                    $code = $item->serviceType->code ?? null;
                    $category = $item->serviceType->category ?? null;
                    if (in_array($code, $roadsideServiceTypes, true) || in_array($category, $roadsideServiceTypes, true)) {
                        return true;
                    }
                }
            }
        } elseif ($this->orderItems()->exists()) {
            return $this->orderItems()
                ->whereHas('serviceType', function ($q) use ($roadsideServiceTypes) {
                    $q->whereIn('code', $roadsideServiceTypes)
                        ->orWhereIn('category', $roadsideServiceTypes);
                })
                ->exists();
        }

        if (isset($this->metadata['source']) && $this->metadata['source'] === 'public_roadside') {
            return true;
        }

        if (isset($this->metadata['service_type'])) {
            return in_array($this->metadata['service_type'], $roadsideServiceTypes, true);
        }

        return false;
    }

    /**
     * Check if this order is handled by a partner.
     */
    public function isHandledByPartner(): bool
    {
        return $this->isRoadside()
            && $this->roadsideDetails?->partner_id !== null;
    }

    /**
     * Check if this order is a road emergency.
     */
    public function isRoadEmergency(): bool
    {
        if (! Schema::hasTable('roadside_emergencies')) {
            return false;
        }

        if ($this->relationLoaded('roadsideEmergency')) {
            return $this->roadsideEmergency !== null;
        }

        return $this->roadsideEmergency()->exists();
    }

    /**
     * Check if this order is a vehicle inspection.
     */
    public function isInspection(): bool
    {
        if (! Schema::hasTable('vehicle_inspection_requests')) {
            return false;
        }

        if ($this->relationLoaded('vehicleInspection')) {
            return $this->vehicleInspection !== null;
        }

        return $this->vehicleInspection()->exists();
    }

    /**
     * Check if this order is eco disposal.
     *
     * Lifecycle mapping (ECO_DISPOSAL) onto global statuses:
     * - pending          ~ PENDING_PAYMENT / DRAFT
     * - confirmed        ~ SCHEDULED / ASSIGNED
     * - in_progress      ~ IN_PROGRESS
     * - completed        ~ COMPLETED (eligible for EcoCertificate)
     * - cancelled        ~ CANCELLED_BY_CUSTOMER / CANCELLED_BY_OPERATOR
     */
    /**
     * Check if this order is an errand order.
     */
    public function isErrand(): bool
    {
        return $this->service_type === ServiceType::ERRAND->value;
    }

    /**
     * Check if this order is eco disposal.
     */
    public function isEcoDisposal(): bool
    {
        if ($this->service_type === ServiceType::ECO_DISPOSAL->value) {
            return true;
        }

        if (Schema::hasTable('disposal_order_details')) {
            if ($this->relationLoaded('disposalDetails')) {
                return $this->disposalDetails !== null;
            }

            if ($this->disposalDetails()->exists()) {
                return true;
            }
        }

        if (isset($this->metadata['service_type']) && $this->metadata['service_type'] === 'eco_disposal') {
            return true;
        }

        if ($this->orderItems()->exists()) {
            return $this->orderItems()->whereHas('serviceType', function ($q) {
                $q->where('code', 'eco_disposal')
                    ->orWhere('category', 'eco_disposal');
            })->exists();
        }

        return false;
    }

    /**
     * Scope only eco disposal orders.
     */
    public function scopeEcoDisposal($query)
    {
        return $query->where(function ($q) {
            if (Schema::hasTable('disposal_order_details')) {
                $q->whereHas('disposalDetails');
            }

            $q->orWhere('metadata->service_type', 'eco_disposal')
                ->orWhereHas('orderItems.serviceType', function ($sq) {
                    $sq->where('code', 'eco_disposal')
                        ->orWhere('category', 'eco_disposal');
                });
        });
    }

    /**
     * Check if this order is social care.
     */
    public function isSocialCare(): bool
    {
        if ($this->service_type === ServiceType::SOCIAL_CARE_VISIT->value) {
            return true;
        }

        if (Schema::hasTable('care_order_details')) {
            if ($this->relationLoaded('careDetails')) {
                return $this->careDetails !== null;
            }
            if ($this->careDetails()->exists()) {
                return true;
            }
        }

        if (isset($this->metadata['service_type']) && $this->metadata['service_type'] === 'social_care_visit') {
            return true;
        }
        if ($this->orderItems()->exists()) {
            return $this->orderItems()->whereHas('serviceType', function ($q) {
                $q->where('code', 'social_care_visit')
                    ->orWhere('category', 'social_care');
            })->exists();
        }

        return false;
    }

    /**
     * Scope only social care orders.
     */
    public function scopeSocialCare($query)
    {
        return $query->where(function ($q) {
            if (Schema::hasTable('care_order_details')) {
                $q->whereHas('careDetails');
            }

            $q->orWhere('metadata->service_type', 'social_care_visit')
                ->orWhereHas('orderItems.serviceType', function ($sq) {
                    $sq->where('code', 'social_care_visit')
                        ->orWhere('category', 'social_care');
                });
        });
    }

    /**
     * Determine if order can be scheduled as eco disposal (assign slot/team).
     */
    public function canBeScheduledAsEcoDisposal(): bool
    {
        if (! $this->isEcoDisposal()) {
            return false;
        }

        // ECO_DISPOSAL can be scheduled when still pending (payment/confirmation)
        return in_array($this->status, ['pending', 'confirmed'], true);
    }

    /**
     * Determine if order can be marked as completed as eco disposal.
     */
    public function canBeMarkedAsCompletedEcoDisposal(): bool
    {
        if (! $this->isEcoDisposal()) {
            return false;
        }

        // For now allow completion from in_progress or confirmed
        return in_array($this->status, ['in_progress', 'confirmed'], true);
    }

    /**
     * Check if this order is a tow service.
     */
    public function isTow(): bool
    {
        if ($this->isRoadEmergency()) {
            $emergency = $this->roadsideEmergency;

            return $emergency && in_array($emergency->incident_type, ['tow_needed', 'accident']);
        }

        return false;
    }

    /**
     * Suggest roadside partner for this order.
     */
    public function suggestRoadsidePartner(): ?Partner
    {
        if (! $this->isRoadside()) {
            return null;
        }

        $zoneId = $this->geo_zone_id ?? null;

        $query = Partner::roadside()
            ->where(function ($q) {
                $q->where('active', true)
                    ->orWhere('is_active', true);
            })
            ->where(function ($q) {
                $q->where('is_available', true)
                    ->orWhereNull('is_available');
            });

        // Р В¤РЎвЂ“Р В»РЎРЉРЎвЂљРЎР‚РЎС“РЎвЂќР СР С• Р С—Р С• Р С–Р ВµР С•Р В·Р С•Р Р…РЎвЂ“, РЎРЏР С”РЎвЂ°Р С• Р Р†Р С•Р Р…Р В° РЎвЂќ
        if ($zoneId) {
            $query->where(function ($q) use ($zoneId) {
                $q->where('geo_zone_id', $zoneId)
                    ->orWhereHas('zones', function ($zq) use ($zoneId) {
                        $zq->where('geo_zones.id', $zoneId);
                    });
            });
        }

        // Р РЋР С•РЎР‚РЎвЂљРЎС“РЎвЂќР СР С• Р С—Р С• Р С—РЎР‚РЎвЂ“Р С•РЎР‚Р С‘РЎвЂљР ВµРЎвЂљРЎС“ (Р СР ВµР Р…РЎв‚¬Р Вµ = Р Р†Р С‘РЎвЂ°Р Вµ Р С—РЎР‚РЎвЂ“Р С•РЎР‚Р С‘РЎвЂљР ВµРЎвЂљ)
        return $query->orderBy('priority')->first();
    }

    /**
     * Scope to get orders by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get orders by priority.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to get orders assigned to a specific user.
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Generate a unique order number.
     */
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-'.date('Ymd').'-'.strtoupper(substr(md5(uniqid()), 0, 6));
        } while (self::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}
