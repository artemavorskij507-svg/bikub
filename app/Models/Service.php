<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations; // <-- Додано

class Service extends Model
{
    use HasFactory, HasTranslations, SoftDeletes; // <-- Додано HasTranslations

    protected $fillable = [
        'title',
        'description',
        'slug',
        'is_active',
        // ... інші поля
    ];

    /**
     * Поля, які підтримують переклад.
     */
    public $translatable = ['title', 'description']; // <-- Додано

    // ... існуюча логіка моделі
}
