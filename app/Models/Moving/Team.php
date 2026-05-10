<?php

namespace App\Models\Moving;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'leader_id',
        'status',
        'max_orders',
        'rating',
        'completed_orders_count',
        'specializations',
        'metadata',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'max_orders' => 'integer',
        'completed_orders_count' => 'integer',
        'specializations' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the team leader.
     */
    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    /**
     * Get the executors in this team.
     */
    public function executors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get the moving orders assigned to this team.
     */
    public function movingOrders(): HasMany
    {
        return $this->hasMany(MovingOrder::class, 'executor_team_id');
    }

    /**
     * Get active orders for this team.
     */
    public function activeOrders()
    {
        return $this->movingOrders()
            ->whereIn('status', ['confirmed', 'in_progress']);
    }

    /**
     * Check if team can accept new orders.
     */
    public function canAcceptOrder(): bool
    {
        return $this->status === 'active'
            && $this->activeOrders()->count() < $this->max_orders;
    }

    /**
     * Find optimal team for a moving order.
     */
    public static function findOptimalTeam(MovingOrder $order): ?self
    {
        return self::where('status', 'active')
            ->whereHas('executors', function ($query) use ($order) {
                $query->whereHas('executorProfile', function ($q) use ($order) {
                    if ($order->total_volume) {
                        $q->where('max_volume', '>=', $order->total_volume);
                    }
                    if ($order->total_weight) {
                        $q->where('max_weight', '>=', $order->total_weight);
                    }
                    if ($order->services) {
                        foreach ($order->services as $service => $enabled) {
                            if ($enabled) {
                                $q->whereJsonContains('skills', $service);
                            }
                        }
                    }
                });
            })
            ->whereDoesntHave('activeOrders', function ($query) {
                $query->whereRaw('(SELECT COUNT(*) FROM moving_orders WHERE executor_team_id = teams.id AND status IN (\'confirmed\', \'in_progress\')) >= teams.max_orders');
            })
            ->orderBy('rating', 'desc')
            ->orderBy('completed_orders_count', 'desc')
            ->first();
    }

    /**
     * Scope to get only active teams.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
