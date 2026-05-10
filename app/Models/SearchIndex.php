<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SearchIndex extends Model
{
    use HasFactory;

    protected $fillable = [
        'org_id',
        'entity_type',
        'entity_id',
        'title',
        'content',
        'facets',
        'metadata',
    ];

    protected $casts = [
        'facets' => 'array',
        'metadata' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function entity(): MorphTo
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    public function scopeForOrganization($query, $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    public function scopeByEntityType($query, $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('title', 'LIKE', "%{$searchTerm}%")
                ->orWhere('content', 'LIKE', "%{$searchTerm}%");
        });
    }

    public function scopeWithFacets($query, array $facets)
    {
        foreach ($facets as $facet => $value) {
            $query->whereJsonContains("facets->{$facet}", $value);
        }

        return $query;
    }

    public function updateFromEntity($entity): void
    {
        $this->update([
            'title' => $this->extractTitle($entity),
            'content' => $this->extractContent($entity),
            'facets' => $this->extractFacets($entity),
            'metadata' => $this->extractMetadata($entity),
        ]);
    }

    private function extractTitle($entity): string
    {
        return match ($this->entity_type) {
            'service' => $entity->name ?? '',
            'product' => $entity->name ?? '',
            'partner' => $entity->name ?? '',
            default => $entity->name ?? $entity->title ?? ''
        };
    }

    private function extractContent($entity): string
    {
        $content = [];

        if (isset($entity->description)) {
            $content[] = $entity->description;
        }

        if (isset($entity->category)) {
            $content[] = $entity->category;
        }

        if (isset($entity->tags)) {
            $content[] = is_array($entity->tags) ? implode(' ', $entity->tags) : $entity->tags;
        }

        return implode(' ', $content);
    }

    private function extractFacets($entity): array
    {
        $facets = [];

        if (isset($entity->category)) {
            $facets['category'] = $entity->category;
        }

        if (isset($entity->price)) {
            $facets['price_range'] = $this->getPriceRange($entity->price);
        }

        if (isset($entity->zone)) {
            $facets['zone'] = $entity->zone;
        }

        if (isset($entity->partner_id)) {
            $facets['partner'] = $entity->partner_id;
        }

        return $facets;
    }

    private function extractMetadata($entity): array
    {
        return [
            'price' => $entity->price ?? null,
            'rating' => $entity->rating ?? null,
            'availability' => $entity->is_available ?? true,
            'created_at' => $entity->created_at ?? null,
            'updated_at' => $entity->updated_at ?? null,
        ];
    }

    private function getPriceRange($price): string
    {
        if ($price < 100) {
            return 'under_100';
        }
        if ($price < 300) {
            return '100_300';
        }
        if ($price < 500) {
            return '300_500';
        }
        if ($price < 1000) {
            return '500_1000';
        }

        return 'over_1000';
    }
}
