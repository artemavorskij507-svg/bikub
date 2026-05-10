<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SearchIndex;
use App\Models\SearchSynonym;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'org_id' => 'required|uuid',
            'entity_type' => 'sometimes|string|in:service,product,partner',
            'facets' => 'sometimes|array',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:50',
            'language' => 'sometimes|string|in:ru,no,en',
        ]);

        $orgId = $request->org_id;
        $query = $request->q;
        $entityType = $request->entity_type;
        $facets = $request->facets ?? [];
        $page = $request->page ?? 1;
        $perPage = $request->per_page ?? 20;
        $language = $request->language ?? 'ru';

        // Expand query with synonyms
        $expandedQuery = $this->expandQueryWithSynonyms($query, $orgId, $language);

        // Build search query
        $searchQuery = SearchIndex::forOrganization($orgId)
            ->search($expandedQuery);

        if ($entityType) {
            $searchQuery->byEntityType($entityType);
        }

        if (! empty($facets)) {
            $searchQuery->withFacets($facets);
        }

        // Execute search with pagination
        $results = $searchQuery
            ->orderBy('title')
            ->paginate($perPage, ['*'], 'page', $page);

        // Get facet counts for filtering
        $facetCounts = $this->getFacetCounts($orgId, $expandedQuery, $entityType);

        return response()->json([
            'query' => $query,
            'expanded_query' => $expandedQuery,
            'results' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
            'facets' => $facetCounts,
            'suggestions' => $this->getSuggestions($query, $orgId, $language),
        ]);
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:50',
            'org_id' => 'required|uuid',
            'entity_type' => 'sometimes|string|in:service,product,partner',
            'limit' => 'sometimes|integer|min:1|max:20',
        ]);

        $orgId = $request->org_id;
        $query = $request->q;
        $entityType = $request->entity_type;
        $limit = $request->limit ?? 10;

        $results = SearchIndex::forOrganization($orgId)
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "{$query}%")
                    ->orWhere('title', 'LIKE', "% {$query}%");
            })
            ->when($entityType, function ($q) use ($entityType) {
                return $q->byEntityType($entityType);
            })
            ->select('title', 'entity_type', 'entity_id')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'text' => $item->title,
                    'type' => $item->entity_type,
                    'id' => $item->entity_id,
                ];
            });

        return response()->json([
            'query' => $query,
            'suggestions' => $results,
        ]);
    }

    public function getFacets(Request $request): JsonResponse
    {
        $request->validate([
            'org_id' => 'required|uuid',
            'entity_type' => 'sometimes|string|in:service,product,partner',
        ]);

        $orgId = $request->org_id;
        $entityType = $request->entity_type;

        $facets = $this->getFacetCounts($orgId, '', $entityType);

        return response()->json([
            'facets' => $facets,
        ]);
    }

    public function indexEntity(Request $request): JsonResponse
    {
        $request->validate([
            'org_id' => 'required|uuid',
            'entity_type' => 'required|string|in:service,product,partner',
            'entity_id' => 'required|uuid',
        ]);

        $orgId = $request->org_id;
        $entityType = $request->entity_type;
        $entityId = $request->entity_id;

        // Get the entity
        $entity = $this->getEntity($entityType, $entityId);
        if (! $entity) {
            return response()->json(['error' => 'Entity not found'], 404);
        }

        // Create or update search index
        $searchIndex = SearchIndex::updateOrCreate(
            [
                'org_id' => $orgId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ],
            [
                'title' => $this->extractTitle($entity),
                'content' => $this->extractContent($entity),
                'facets' => $this->extractFacets($entity),
                'metadata' => $this->extractMetadata($entity),
            ]
        );

        return response()->json([
            'message' => 'Entity indexed successfully',
            'index' => $searchIndex,
        ]);
    }

    public function removeEntity(Request $request): JsonResponse
    {
        $request->validate([
            'org_id' => 'required|uuid',
            'entity_type' => 'required|string|in:service,product,partner',
            'entity_id' => 'required|uuid',
        ]);

        $deleted = SearchIndex::forOrganization($request->org_id)
            ->byEntityType($request->entity_type)
            ->where('entity_id', $request->entity_id)
            ->delete();

        return response()->json([
            'message' => 'Entity removed from search index',
            'deleted' => $deleted > 0,
        ]);
    }

    private function expandQueryWithSynonyms(string $query, string $orgId, string $language): string
    {
        $terms = explode(' ', $query);
        $expandedTerms = [];

        foreach ($terms as $term) {
            $expandedTerms[] = $term;

            // Find synonyms
            $synonyms = SearchSynonym::where('org_id', $orgId)
                ->where('language', $language)
                ->where('term', $term)
                ->first();

            if ($synonyms && is_array($synonyms->synonyms)) {
                $expandedTerms = array_merge($expandedTerms, $synonyms->synonyms);
            }
        }

        return implode(' ', array_unique($expandedTerms));
    }

    private function getFacetCounts(string $orgId, string $query = '', ?string $entityType = null): array
    {
        $baseQuery = SearchIndex::forOrganization($orgId);

        if ($query) {
            $baseQuery->search($query);
        }

        if ($entityType) {
            $baseQuery->byEntityType($entityType);
        }

        $facets = [];

        // Category facets
        $categoryFacets = $baseQuery->clone()
            ->select(DB::raw('JSON_EXTRACT(facets, "$.category") as category'), DB::raw('COUNT(*) as count'))
            ->whereNotNull(DB::raw('JSON_EXTRACT(facets, "$.category")'))
            ->groupBy('category')
            ->get()
            ->pluck('count', 'category')
            ->toArray();

        $facets['category'] = $categoryFacets;

        // Price range facets
        $priceFacets = $baseQuery->clone()
            ->select(DB::raw('JSON_EXTRACT(facets, "$.price_range") as price_range'), DB::raw('COUNT(*) as count'))
            ->whereNotNull(DB::raw('JSON_EXTRACT(facets, "$.price_range")'))
            ->groupBy('price_range')
            ->get()
            ->pluck('count', 'price_range')
            ->toArray();

        $facets['price_range'] = $priceFacets;

        // Zone facets
        $zoneFacets = $baseQuery->clone()
            ->select(DB::raw('JSON_EXTRACT(facets, "$.zone") as zone'), DB::raw('COUNT(*) as count'))
            ->whereNotNull(DB::raw('JSON_EXTRACT(facets, "$.zone")'))
            ->groupBy('zone')
            ->get()
            ->pluck('count', 'zone')
            ->toArray();

        $facets['zone'] = $zoneFacets;

        return $facets;
    }

    private function getSuggestions(string $query, string $orgId, string $language): array
    {
        // Simple suggestions based on common typos or similar terms
        $suggestions = [];

        if (strlen($query) > 3) {
            $similar = SearchIndex::forOrganization($orgId)
                ->where('title', 'LIKE', "%{$query}%")
                ->where('title', '!=', $query)
                ->select('title')
                ->distinct()
                ->limit(5)
                ->pluck('title')
                ->toArray();

            $suggestions = array_slice($similar, 0, 3);
        }

        return $suggestions;
    }

    private function getEntity(string $entityType, string $entityId)
    {
        return match ($entityType) {
            'service' => \App\Models\ServiceType::find($entityId),
            'product' => \App\Models\RestaurantMenuItem::find($entityId) ?? \App\Models\RetailStoreItem::find($entityId),
            'partner' => \App\Models\Partner::find($entityId),
            default => null
        };
    }

    private function extractTitle($entity): string
    {
        return $entity->name ?? $entity->title ?? '';
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
