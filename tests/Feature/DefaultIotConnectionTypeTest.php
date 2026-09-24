<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class DefaultIotConnectionTypeTest extends TestCase
{
    public function test_tenant_defaults_seed_bridge_as_the_iot_connection_type(): void
    {
        $migration = file_get_contents(database_path('migrations/tenant/2026_08_31_210000_seed_default_tenant_records.php'));
        $existingTenantMigration = file_get_contents(database_path('migrations/tenant/2026_09_24_050006_add_default_iot_connection_type_to_system_configs.php'));
        $seeder = file_get_contents(database_path('seeders/Tenant/SettingSeeder.php'));

        $defaultSetting = "['key' => 'sys_iot_connection_type', 'value' => 'bridge', 'group' => 'system']";

        $this->assertIsString($migration);
        $this->assertStringContainsString($defaultSetting, $migration);
        $this->assertIsString($seeder);
        $this->assertStringContainsString($defaultSetting, $seeder);
        $this->assertIsString($existingTenantMigration);
        $this->assertStringContainsString("'value' => 'bridge'", $existingTenantMigration);
        $this->assertStringContainsString('insertOrIgnore', $existingTenantMigration);
        $this->assertStringContainsString("if (\$setting['key'] === 'sys_iot_connection_type')", $seeder);
        $this->assertStringContainsString('Setting::firstOrCreate(', $seeder);
    }
}
