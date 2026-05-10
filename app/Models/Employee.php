<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'partner_id',
        'employee_number',
        'first_name',
        'last_name',
        'phone',
        'email',
        'position',
        'status',
        'is_verified',
        'background_check',
        'hire_date',
        'skills',
        'metadata',
    ];

    protected $casts = [
        'skills' => 'array',
        'metadata' => 'array',
        'hire_date' => 'date',
        'is_verified' => 'boolean',
        'background_check' => 'boolean',
        'current_location' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function currentZone()
    {
        return $this->belongsTo(GeoZone::class, 'current_zone_id');
    }

    public function scheduleSlots()
    {
        return $this->belongsToMany(ScheduleSlot::class, 'schedule_slot_employees', 'employee_id', 'slot_id')
            ->withPivot(['skills', 'lead'])
            ->withTimestamps();
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public static function generateEmployeeNumber(): string
    {
        do {
            $number = 'EMP-'.date('Y').'-'.str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::where('employee_number', $number)->exists());

        return $number;
    }
}
