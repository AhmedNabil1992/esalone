<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Branch;
use App\Models\Tenant\CashBox;
use App\Models\Tenant\InventoryGroup;
use App\Models\Tenant\Warehouse;
use Illuminate\Database\Seeder;

class DefaultSeeder extends Seeder
{
    public function run(): void
    {
        $mainBranch = Branch::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'الفرع الرئيسي',
                'code' => 'MAIN',
                'is_active' => true,
            ]
        );

        CashBox::firstOrCreate(
            ['code' => '1'],
            [
                'branch_id' => $mainBranch->id,
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
                'branch_id' => $mainBranch->id,
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
