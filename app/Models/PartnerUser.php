<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'user_id',
        'role',
    ];

    /**
     * Get the partner for this user.
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the user for this partner.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user has specific role for partner.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has admin role.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user has manager role or higher.
     */
    public function isManager(): bool
    {
        return in_array($this->role, ['admin', 'manager']);
    }

    /**
     * Scope to get users by role.
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to get admin users.
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope to get manager users.
     */
    public function scopeManagers($query)
    {
        return $query->whereIn('role', ['admin', 'manager']);
    }
}
