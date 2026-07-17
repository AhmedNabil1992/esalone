<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\CashBox;
use App\Models\Tenant\InventoryGroup;
use App\Models\Tenant\Warehouse;
use Illuminate\Database\Seeder;

class DefaultSeeder extends Seeder
{
    public function run(): void
    {
        // Create default cash box
        CashBox::firstOrCreate(
            ['code' => '1'],
            [
                'name' => 'الخزنة الرئيسية',
                'type' => 'cash',
                'current_balance' => 0,
                'is_active' => true,
            ]
        );

        // Create default warehouse
        Warehouse::firstOrCreate(
            ['code' => '1'],
            [
                'name' => 'المخزن الرئيسي',
                'is_active' => true,
            ]
        );

        InventoryGroup::firstOrCreate(
            ['name' => 'مكونات'],
            [
                'is_active' => true,
            ]
        );
    }
}
