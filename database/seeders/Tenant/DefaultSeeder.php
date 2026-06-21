<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\CashBox;
use App\Models\Tenant\Warehouse;
use Illuminate\Database\Seeder;

class DefaultSeeder extends Seeder
{
    public function run(): void
    {
        // Create default cash box
        CashBox::create([
            'name' => 'الخزنة الرئيسية',
            'type' => 'cash',
            'code' => '1',
            'current_balance' => 0,
            'is_active' => true,
        ]);

        // Create default warehouse
        Warehouse::create([
            'name' => 'المخزن الرئيسي',
            'code' => '1',
            'is_active' => true,
        ]);
    }
}
