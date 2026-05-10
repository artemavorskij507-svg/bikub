<?php

namespace App\Models\Moving;

use App\Models\HandymanMaterialsEntry;
use App\Models\RepairProject;
use App\Models\RepairTeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vehicle_type',
        'skills',
        'max_volume',
        'max_weight',
        'insurance_limit',
        'license_number',
        'license_expires_at',
        'rating',
        'completed_orders_count',
        'is_active',
        'last_active_at',
        'metadata',
    ];

    protected $casts = [
        'skills' => 'array',
        'max_volume' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'insurance_limit' => 'decimal:2',
        'rating' => 'decimal:2',
        'license_expires_at' => 'date',
        'is_active' => 'boolean',
        'last_active_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns this executor profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if executor has a specific skill.
     */
    public function hasSkill(string $skill): bool
    {
        return in_array($skill, $this->skills ?? []);
    }

    /**
     * Check if executor can handle volume.
     */
    public function canHandleVolume(float $volume): bool
    {
        return $this->max_volume && $this->max_volume >= $volume;
    }

    /**
     * Check if executor can handle weight.
     */
    public function canHandleWeight(float $weight): bool
    {
        return $this->max_weight && $this->max_weight >= $weight;
    }

    /**
     * Scope to get only active executors.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by vehicle type.
     */
    public function scopeByVehicleType($query, string $vehicleType)
    {
        return $query->where('vehicle_type', $vehicleType);
    }

    /**
     * Scope to filter by skills.
     */
    public function scopeWithSkills($query, array $skills)
    {
        foreach ($skills as $skill) {
            $query->whereJsonContains('skills', $skill);
        }

        return $query;
    }

    public function repairTeamMemberships()
    {
        return $this->hasMany(RepairTeamMember::class, 'executor_profile_id');
    }

    public function materialsEntries()
    {
        return $this->hasMany(HandymanMaterialsEntry::class, 'executor_profile_id');
    }

    public function managedRepairProjects()
    {
        return $this->hasMany(RepairProject::class, 'project_manager_id');
    }

    public function handymanAssignments()
    {
        return $this->hasMany(\App\Models\HandymanAssignment::class, 'executor_profile_id');
    }

    public function kpi()
    {
        return $this->hasOne(\App\Models\HandymanKpiSnapshot::class, 'executor_profile_id');
    }

    public function reviews()
    {
        return $this->hasMany(\App\Models\OrderReview::class, 'executor_profile_id');
    }

    /**
     * Get the name attribute (from related user).
     */
    public function getNameAttribute(): ?string
    {
        return $this->user?->name;
    }
}
