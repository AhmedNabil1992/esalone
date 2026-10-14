<?php

declare(strict_types=1);

namespace Database\Seeders\Tenant;

use App\Models\Tenant\InventoryGroup;
use Illuminate\Database\Seeder;

class DefaultCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $inventoryGroups = [
            ['name' => 'منتجات العناية بالشعر', 'description' => 'الشامبو والزيوت ومنتجات تصفيف الشعر'],
            ['name' => 'منتجات العناية بالبشرة', 'description' => 'منظفات البشرة والكريمات وأدوات العناية'],
            ['name' => 'مستحضرات التجميل', 'description' => 'مستحضرات المكياج والتجميل للبيع'],
            ['name' => 'مستلزمات الصالون', 'description' => 'الأدوات والمواد الاستهلاكية المستخدمة في الصالون'],
        ];

        foreach ($inventoryGroups as $inventoryGroup) {
            InventoryGroup::query()->firstOrCreate(
                ['name' => $inventoryGroup['name']],
                [
                    'description' => $inventoryGroup['description'],
                    'is_active' => true,
                ],
            );
        }
    }
}
