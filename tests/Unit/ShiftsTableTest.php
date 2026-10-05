<?php

namespace Tests\Unit;

use App\Filament\Resources\Tenant\Shifts\Shifts\Tables\ShiftsTable;
use Filament\Actions\ActionGroup;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Tests\TestCase;

class ShiftsTableTest extends TestCase
{
    public function test_closed_shift_report_action_is_available_in_shift_history(): void
    {
        $livewire = $this->createMock(HasTable::class);
        $table = ShiftsTable::configure(new Table($livewire));
        $actionGroup = collect($table->getRecordActions())
            ->first(fn ($action): bool => $action instanceof ActionGroup);
        $groupedActions = collect($actionGroup?->getActions() ?? [])
            ->keyBy(fn ($action): string => $action->getName());
        $reportAction = $groupedActions->get('shift_close_report');

        $this->assertNotNull($actionGroup);
        $this->assertArrayHasKey('view', $groupedActions->all());
        $this->assertArrayHasKey('close', $groupedActions->all());
        $this->assertNotNull($reportAction);
        $this->assertSame('عرض / طباعة التقرير', $reportAction->getLabel());
        $this->assertTrue($reportAction->shouldOpenUrlInNewTab());
    }
}
