<?php

namespace App\Modules\Classifieds\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Shop extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'logo_path',
        'cover_path',
        'phone',
        'website',
        'address',
        'working_hours',
        'is_verified',
        'is_active',
    ];

    protected $casts = [
        'working_hours' => 'array',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ads()
    {
        return $this->hasMany(\App\Modules\Classifieds\Models\ClassifiedAd::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Shop $shop) {
            if (empty($shop->slug)) {
                $shop->slug = Str::slug($shop->name);
            }
        });
    }
}
