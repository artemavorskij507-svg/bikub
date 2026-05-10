<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcoCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'certificate_uid',
        'customer_name',
        'summary_data',
        'co2_saved_kg',
        'items_reused_count',
        'issued_at',
        'pdf_path',
    ];

    protected $casts = [
        'summary_data' => 'array',
        'co2_saved_kg' => 'decimal:3',
        'items_reused_count' => 'integer',
        'issued_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
