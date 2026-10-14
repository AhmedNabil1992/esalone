<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\CatalogItem;
use App\Models\Tenant\ProductComponent;
use App\Models\Tenant\SalonService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaveSalonServiceAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?SalonService $salonService = null): SalonService
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'is_active' => ['required', 'boolean'],
            'component_usages' => ['sometimes', 'array', 'max:30'],
            'component_usages.*.component_id' => ['required', 'integer', 'distinct', Rule::exists('catalog_items', 'id')],
            'component_usages.*.quantity' => ['required', 'numeric', 'min:0.001', 'max:999999.999'],
            'component_usages.*.notes' => ['nullable', 'string', 'max:100'],
        ])->validate();

        $shouldSyncComponents = array_key_exists('component_usages', $data);
        $componentUsages = $validated['component_usages'] ?? [];

        return DB::transaction(function () use ($validated, $salonService, $shouldSyncComponents, $componentUsages): SalonService {
            $componentIds = collect($componentUsages)->pluck('component_id')->map(fn ($id): int => (int) $id);
            $components = CatalogItem::query()
                ->whereIn('id', $componentIds)
                ->where('is_component', true)
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

            if ($components->count() !== $componentIds->count()) {
                throw ValidationException::withMessages([
                    'component_usages' => 'اختر خامات نشطة ومحددة للاستخدام كمكونات.',
                ]);
            }

            $service = $salonService ?? new SalonService;
            $service->fill(collect($validated)->except('component_usages')->all());
            $service->save();

            $catalogItem = $service->catalogItem ?? new CatalogItem;
            $hasComponents = $shouldSyncComponents
                ? $components->isNotEmpty()
                : $catalogItem->components()->exists();
            $catalogItem->fill([
                'name' => Str::limit($service->name, 20, ''),
                'description' => Str::limit((string) $service->description, 50, ''),
                'purchase_price' => 0,
                'sale_price' => $service->price,
                'is_product' => $hasComponents,
                'is_component' => false,
                'is_active' => $service->is_active,
            ]);
            $catalogItem->save();

            $service->catalogItem()->associate($catalogItem);
            $service->saveQuietly();

            if ($shouldSyncComponents) {
                $catalogItem->components()->delete();

                foreach ($componentUsages as $componentUsage) {
                    ProductComponent::query()->create([
                        'product_id' => $catalogItem->getKey(),
                        'component_id' => (int) $componentUsage['component_id'],
                        'quantity' => $componentUsage['quantity'],
                        'notes' => $componentUsage['notes'] ?? null,
                    ]);
                }
            }

            return $service->refresh();
        });
    }
}
