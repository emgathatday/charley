<?php

namespace App\Services\Library;

use App\Models\KnowledgeDomain;
use App\Models\LibraryItem;
use App\Models\LibraryItemHotspot;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LibraryItemHotspotService
{
    public function create(LibraryItem $libraryItem, KnowledgeDomain $knowledgeDomain, array $attributes): LibraryItemHotspot
    {
        return DB::transaction(function () use ($libraryItem, $knowledgeDomain, $attributes): LibraryItemHotspot {
            $attributes = $this->normalizeAttributes($attributes);

            return LibraryItemHotspot::query()->create($attributes + [
                'library_item_id' => $libraryItem->id,
                'knowledge_domain_id' => $knowledgeDomain->id,
            ]);
        });
    }

    public function update(LibraryItemHotspot $hotspot, array $attributes): LibraryItemHotspot
    {
        return DB::transaction(function () use ($hotspot, $attributes): LibraryItemHotspot {
            $hotspot->update($this->normalizeAttributes($attributes, partial: true));

            return $hotspot->refresh()->load(['libraryItem', 'knowledgeDomain']);
        });
    }

    public function delete(LibraryItemHotspot $hotspot): void
    {
        DB::transaction(function () use ($hotspot): void {
            $hotspot->delete();
        });
    }

    public function replaceForLibraryItem(LibraryItem $libraryItem, array $hotspots): LibraryItem
    {
        return DB::transaction(function () use ($libraryItem, $hotspots): LibraryItem {
            LibraryItemHotspot::query()->where('library_item_id', $libraryItem->id)->delete();

            foreach ($hotspots as $index => $hotspot) {
                $knowledgeDomainId = $hotspot['knowledge_domain_id'] ?? null;
                if (! $knowledgeDomainId) {
                    throw new InvalidArgumentException('Each hotspot must reference a knowledge_domain_id.');
                }

                LibraryItemHotspot::query()->create($this->normalizeAttributes($hotspot) + [
                    'library_item_id' => $libraryItem->id,
                    'knowledge_domain_id' => $knowledgeDomainId,
                    'sort_order' => $hotspot['sort_order'] ?? $index + 1,
                ]);
            }

            return $libraryItem->refresh();
        });
    }

    public function reorder(LibraryItem $libraryItem, array $orderedHotspotIds): void
    {
        DB::transaction(function () use ($libraryItem, $orderedHotspotIds): void {
            foreach (array_values($orderedHotspotIds) as $index => $hotspotId) {
                LibraryItemHotspot::query()
                    ->where('library_item_id', $libraryItem->id)
                    ->whereKey($hotspotId)
                    ->update(['sort_order' => $index + 1]);
            }
        });
    }

    private function normalizeAttributes(array $attributes, bool $partial = false): array
    {
        if (! $partial || array_key_exists('shape_type', $attributes)) {
            $shapeType = $attributes['shape_type'] ?? LibraryItemHotspot::SHAPE_POLYGON;
            if (! in_array($shapeType, LibraryItemHotspot::SHAPES, true)) {
                throw new InvalidArgumentException('Invalid library item hotspot shape_type.');
            }
            $attributes['shape_type'] = $shapeType;
        }

        if (! $partial || array_key_exists('coordinates', $attributes)) {
            $coordinates = $attributes['coordinates'] ?? null;
            if (! is_array($coordinates) || $coordinates === []) {
                throw new InvalidArgumentException('Library item hotspot coordinates must be a non-empty array.');
            }
            $attributes['coordinates'] = $coordinates;
        }

        return array_intersect_key($attributes, array_flip([
            'knowledge_domain_id',
            'label',
            'shape_type',
            'coordinates',
            'sort_order',
        ]));
    }
}