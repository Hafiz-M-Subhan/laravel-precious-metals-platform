<?php

namespace App\Services;

use App\Models\Asset;
use Elastic\Elasticsearch\Client;
use Illuminate\Support\Facades\Log;

class AssetSearchService
{
    private const INDEX = 'precious_metals_assets';

    public function __construct(private readonly Client $elasticsearch) {}

    public function indexAsset(Asset $asset): void
    {
        $this->elasticsearch->index([
            'index' => self::INDEX,
            'id'    => $asset->id,
            'body'  => [
                'symbol'           => $asset->symbol,
                'name'             => $asset->name,
                'unit'             => $asset->unit,
                'currency'         => $asset->currency,
                'spot_price'       => (float) $asset->spot_price,
                'daily_change_pct' => (float) $asset->daily_change_pct,
                'is_active'        => $asset->is_active,
                'updated_at'       => $asset->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function updatePrice(Asset $asset): void
    {
        try {
            $this->elasticsearch->update([
                'index' => self::INDEX,
                'id'    => $asset->id,
                'body'  => [
                    'doc' => [
                        'spot_price'       => (float) $asset->spot_price,
                        'daily_change_pct' => (float) $asset->daily_change_pct,
                        'updated_at'       => now()->toIso8601String(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            // ES is non-critical for price updates — log and continue
            Log::warning('ElasticSearch price update failed', [
                'asset_id' => $asset->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    public function search(string $query, array $filters = []): array
    {
        $must = [
            [
                'multi_match' => [
                    'query'     => $query,
                    'fields'    => ['symbol^3', 'name^2', 'unit', 'currency'],
                    'type'      => 'best_fields',
                    'fuzziness' => 'AUTO',
                ],
            ],
        ];

        if (isset($filters['currency'])) {
            $must[] = ['term' => ['currency' => strtoupper($filters['currency'])]];
        }

        $must[] = ['term' => ['is_active' => true]];

        $response = $this->elasticsearch->search([
            'index' => self::INDEX,
            'body'  => [
                'query'   => ['bool' => ['must' => $must]],
                'sort'    => [['_score' => 'desc'], ['symbol' => 'asc']],
                'size'    => 20,
                '_source' => true,
            ],
        ]);

        return collect($response['hits']['hits'])
            ->map(fn ($hit) => array_merge($hit['_source'], [
                'id'    => (int) $hit['_id'],
                'score' => round((float) $hit['_score'], 4),
            ]))
            ->all();
    }

    public function createIndex(): void
    {
        if ($this->elasticsearch->indices()->exists(['index' => self::INDEX])->asBool()) {
            return;
        }

        $this->elasticsearch->indices()->create([
            'index' => self::INDEX,
            'body'  => [
                'settings' => [
                    'number_of_shards'   => 1,
                    'number_of_replicas' => 0,
                ],
                'mappings' => [
                    'properties' => [
                        'symbol'           => ['type' => 'keyword'],
                        'name'             => ['type' => 'text', 'analyzer' => 'standard', 'fields' => ['keyword' => ['type' => 'keyword']]],
                        'unit'             => ['type' => 'keyword'],
                        'currency'         => ['type' => 'keyword'],
                        'spot_price'       => ['type' => 'float'],
                        'daily_change_pct' => ['type' => 'float'],
                        'is_active'        => ['type' => 'boolean'],
                        'updated_at'       => ['type' => 'date'],
                    ],
                ],
            ],
        ]);
    }

    public function deleteIndex(): void
    {
        if ($this->elasticsearch->indices()->exists(['index' => self::INDEX])->asBool()) {
            $this->elasticsearch->indices()->delete(['index' => self::INDEX]);
        }
    }
}
