<?php

namespace App\Filament\Resources\Tenant\Appointments\Schemas;

use App\Models\Tenant\Appointment;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SalonService;
use App\Models\Tenant\StaffMember;
use App\Models\Tenant\SystemConfig;
use App\Models\Tenant\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->label('الفرع')
                    ->options(fn (): array => self::branchOptions())
                    ->default(fn (): ?int => self::defaultBranchId())
                    ->live()
                    ->required(),
                Select::make('customer_id')
                    ->label('العميل')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Select::make('staff_member_id')
                    ->label('الموظف')
                    ->options(fn (Get $get): array => StaffMember::query()
                        ->where('branch_id', $get('branch_id'))
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->required()
                    ->disabled(fn (Get $get): bool => blank($get('branch_id'))),
                Select::make('service_ids')
                    ->label('الخدمات')
                    ->multiple()
                    ->options(fn (Get $get): array => SalonService::query()
                        ->where('branch_id', $get('branch_id'))
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->required()
                    ->minItems(1)
                    ->live()
                    ->disabled(fn (Get $get): bool => blank($get('branch_id')))
                    ->afterStateHydrated(function (Select $component, ?Appointment $record): void {
                        if ($record) {
                            $component->state($record->items->pluck('salon_service_id')->all());
                        }
                    })
                    ->helperText('يمكن اختيار أكثر من خدمة؛ يحدد النظام مدة الموعد وسعره من قائمة الخدمات.'),
                DateTimePicker::make('start_at')
                    ->label('موعد الحضور')
                    ->timezone(SystemConfig::get('sys_system_time_zone', 'Africa/Cairo'))
                    ->seconds(false)
                    ->required(),
                Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'requested' => 'طلب حجز جديد',
                        'scheduled' => 'محجوز',
                        'confirmed' => 'مؤكد',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                        'no_show' => 'لم يحضر',
                    ])
                    ->default('scheduled')
                    ->required(),
                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3)
                    ->maxLength(5000)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function branchOptions(): array
    {
        $query = Branch::query()->where('is_active', true);
        $user = auth()->user();

        if ($user instanceof User && ! $user->hasAnyRole(['owner', 'super_admin', 'Admin'])) {
            $query->whereIn('id', $user->assignedBranchIds());
        }

        return $query->orderBy('name')->pluck('name', 'id')->all();
    }

    private static function defaultBranchId(): ?int
    {
        $activeBranchId = session('active_branch_id');

        if (is_numeric($activeBranchId) && (int) $activeBranchId > 0) {
            return (int) $activeBranchId;
        }

        $user = auth()->user();

        if ($user instanceof User && $user->assignedBranchIds() !== []) {
            return $user->assignedBranchIds()[0];
        }

        return Branch::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');
    }
}
