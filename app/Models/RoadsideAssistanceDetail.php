<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoadsideAssistanceDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'subtype',
        'incident_address',
        'incident_lat',
        'incident_lng',
        'vehicle_make',
        'vehicle_model',
        'vehicle_plate',
        'vehicle_color',
        'partner_id',
        'inspection_report_url',
        'extra',
    ];

    protected $casts = [
        'incident_lat' => 'decimal:7',
        'incident_lng' => 'decimal:7',
        'extra' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function preset()
    {
        // Получаем preset через subtype (code)
        if ($this->subtype) {
            return RoadsidePreset::where('code', $this->subtype)->first();
        }

        return null;
    }
}
