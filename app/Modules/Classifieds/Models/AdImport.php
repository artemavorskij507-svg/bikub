<?php

namespace App\Modules\Classifieds\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AdImport extends Model
{
    protected $fillable = [
        'user_id',
        'shop_id',
        'file_path',
        'file_type',
        'status',
        'processed_count',
        'error_count',
        'report',
    ];

    protected $casts = [
        'report' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
