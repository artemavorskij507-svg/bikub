<?php

namespace App\Modules\Logistics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $fillable = ['user_id','label','address_line_1','address_line_2','city','postal_code','country_code','latitude','longitude','is_default','metadata'];
    protected $casts = ['is_default' => 'boolean','metadata' => 'array'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
