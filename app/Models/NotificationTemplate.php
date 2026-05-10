<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'channel',
        'locale',
        'subject',
        'body',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Render template with variables.
     */
    public function render(array $variables = []): array
    {
        $subject = $this->subject;
        $body = $this->body;

        foreach ($variables as $key => $value) {
            $subject = str_replace("{{$key}}", $value, $subject);
            $body = str_replace("{{$key}}", $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    /**
     * Get template by code, channel and locale.
     */
    public static function getTemplate(string $code, string $channel, string $locale = 'no'): ?self
    {
        return self::where('code', $code)
            ->where('channel', $channel)
            ->where('locale', $locale)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Scope to get templates by channel.
     */
    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope to get templates by locale.
     */
    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope to get active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get available variables for this template.
     */
    public function getAvailableVariables(): array
    {
        return $this->variables ?? [];
    }

    /**
     * Validate variables against template.
     */
    public function validateVariables(array $variables): bool
    {
        $required = $this->getAvailableVariables();

        foreach ($required as $variable) {
            if (! isset($variables[$variable])) {
                return false;
            }
        }

        return true;
    }
}
