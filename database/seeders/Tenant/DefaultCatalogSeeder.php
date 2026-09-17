<?php

declare(strict_types=1);

namespace Database\Seeders\Tenant;

use App\Models\Tenant\DevicesType;
use App\Models\Tenant\InventoryGroup;
use App\Models\Tenant\PlayType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DefaultCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $deviceTypes = [
            [
                'name' => 'بلاي ستيشن',
                'description' => 'أجهزة ألعاب الفيديو المنزلية',
                'picture' => 'device-types/playstation.webp',
                'source' => 'device-types/playstation.webp',
                'fixed_times' => [30, 60, 90, 120],
                'play_types' => [
                    ['name' => 'فردي', 'description' => 'لعب فردي باستخدام يد تحكم واحدة', 'picture' => 'play-types/playstation-single.webp', 'source' => 'play-types/playstation-single.webp'],
                    ['name' => 'زوجي', 'description' => 'لعب مشترك باستخدام يدي تحكم', 'picture' => 'play-types/playstation-double.webp', 'source' => 'play-types/playstation-double.webp'],
                    ['name' => 'نيتفلكس', 'description' => 'مشاهدة الأفلام والمسلسلات', 'picture' => 'play-types/playstation-netflix.webp', 'source' => 'play-types/playstation-netflix.webp'],
                ],
            ],
            [
                'name' => 'بنج بونج',
                'description' => 'طاولات تنس الطاولة',
                'picture' => 'device-types/ping-pong.webp',
                'source' => 'device-types/ping-pong.webp',
                'fixed_times' => [30, 60, 90, 120],
                'play_types' => [
                    ['name' => 'فردي', 'description' => 'مباراة فردية بين لاعبين', 'picture' => 'play-types/ping-pong-single.webp', 'source' => 'play-types/ping-pong-single.webp'],
                    ['name' => 'زوجي', 'description' => 'مباراة زوجية بين أربعة لاعبين', 'picture' => 'play-types/ping-pong-double.webp', 'source' => 'play-types/ping-pong-double.webp'],
                ],
            ],
            [
                'name' => 'بلياردو',
                'description' => 'طاولات البلياردو',
                'picture' => 'device-types/billiards.webp',
                'source' => 'device-types/billiards.webp',
                'fixed_times' => [30, 60, 90, 120],
                'play_types' => [
                    ['name' => 'فردي', 'description' => 'لعب فردي على طاولة البلياردو', 'picture' => 'play-types/billiards-single.webp', 'source' => 'play-types/billiards-single.webp'],
                    ['name' => 'زوجي', 'description' => 'لعب زوجي على طاولة البلياردو', 'picture' => 'play-types/billiards-double.webp', 'source' => 'play-types/billiards-double.webp'],
                ],
            ],
        ];

        foreach ($deviceTypes as $deviceTypeData) {
            $this->copyDefaultAsset($deviceTypeData['source'], $deviceTypeData['picture']);

            $deviceType = DevicesType::query()->firstOrCreate(
                ['name' => $deviceTypeData['name']],
                [
                    'description' => $deviceTypeData['description'],
                    'picture' => $deviceTypeData['picture'],
                    'fixed_times' => $deviceTypeData['fixed_times'],
                    'is_active' => true,
                ],
            );

            foreach ($deviceTypeData['play_types'] as $playTypeData) {
                $this->copyDefaultAsset($playTypeData['source'], $playTypeData['picture']);

                PlayType::query()->firstOrCreate(
                    [
                        'device_type_id' => $deviceType->id,
                        'name' => $playTypeData['name'],
                    ],
                    [
                        'description' => $playTypeData['description'],
                        'picture' => $playTypeData['picture'],
                    ],
                );
            }
        }

        $inventoryGroups = [
            ['name' => 'مشروبات باردة', 'description' => 'المشروبات الغازية والعصائر الباردة', 'picture' => 'inventory-groups/cold-drinks.webp', 'source' => 'inventory-groups/cold-drinks.webp'],
            ['name' => 'مشروبات ساخنة', 'description' => 'القهوة والشاي والمشروبات الساخنة', 'picture' => 'inventory-groups/hot-drinks.webp', 'source' => 'inventory-groups/hot-drinks.webp'],
            ['name' => 'سناكس', 'description' => 'الشيبسي والفشار والمقرمشات', 'picture' => 'inventory-groups/snacks.webp', 'source' => 'inventory-groups/snacks.webp'],
            ['name' => 'وجبات', 'description' => 'الوجبات السريعة والسندوتشات', 'picture' => 'inventory-groups/meals.webp', 'source' => 'inventory-groups/meals.webp'],
        ];

        foreach ($inventoryGroups as $inventoryGroupData) {
            $this->copyDefaultAsset($inventoryGroupData['source'], $inventoryGroupData['picture']);

            InventoryGroup::query()->firstOrCreate(
                ['name' => $inventoryGroupData['name']],
                [
                    'description' => $inventoryGroupData['description'],
                    'picture' => $inventoryGroupData['picture'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function copyDefaultAsset(string $sourcePath, string $targetPath): void
    {
        $source = resource_path('tenant-defaults/'.$sourcePath);

        if (! File::isFile($source)) {
            throw new RuntimeException("Default tenant asset is missing: {$sourcePath}");
        }

        if (! Storage::disk('public')->exists($targetPath)) {
            Storage::disk('public')->put($targetPath, File::get($source));
        }
    }
}
