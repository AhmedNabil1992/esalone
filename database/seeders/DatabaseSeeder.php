<?php

namespace Database\Seeders;

use Database\Seeders\Tenant\DefaultCatalogSeeder;
use Database\Seeders\Tenant\DefaultSeeder;
use Database\Seeders\Tenant\SettingSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (tenancy()->initialized) {
            $this->call([
                DefaultSeeder::class,
                DefaultCatalogSeeder::class,
                SettingSeeder::class,
            ]);
        }
    }
}
