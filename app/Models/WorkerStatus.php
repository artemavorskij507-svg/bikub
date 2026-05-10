<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'is_online',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;

    /**
     * Get the user that owns this worker status.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
