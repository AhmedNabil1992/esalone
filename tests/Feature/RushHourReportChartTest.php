<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RushHourReportChartTest extends TestCase
{
    #[Test]
    public function chart_is_recreated_when_the_serialized_data_changes(): void
    {
        $view = file_get_contents(resource_path('views/filament/pages/rush-hour-report.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('wire:key="rush-hour-chart-{{ md5($hourlyDataSerialized) }}"', $view);
        $this->assertStringContainsString("JSON.parse(this.\$wire.hourlyDataSerialized || '[]')", $view);
        $this->assertStringContainsString('this.chart.destroy();', $view);
        $this->assertStringNotContainsString('wire:ignore', $view);
        $this->assertStringNotContainsString('rush-hour-chart-updated', $view);
    }
}
