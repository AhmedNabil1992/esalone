<?php

declare(strict_types=1);

namespace App\Filament\Customer\Pages;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Devices;
use App\Models\Tenant\Reservation;
use App\Models\Tenant\SystemConfig;
use App\Scopes\BranchScope;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CustomerReservations extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'حجوزاتي';

    protected static ?string $navigationLabel = 'حجوزاتي';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.customer.pages.customer-reservations';

    public function getCustomerProperty(): ?Customer
    {
        return Auth::guard('customer')->user();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Reservation::withoutGlobalScope(BranchScope::class)
                    ->where('customer_id', auth()->guard('customer')->id())
                    ->with(['branch', 'device'])
                    ->latest('id')
            )
            ->columns([
                TextColumn::make('branch.name')
                    ->label('الفرع')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('device.name')
                    ->label('الجهاز')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('start_time')
                    ->label('وقت الحجز')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),
                TextColumn::make('duration_minutes')
                    ->label('المدة (دقيقة)')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'مؤكد',
                        'cancelled' => 'ملغي',
                        'completed' => 'مكتمل',
                        default => $state ?? 'مستمر',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'gray',
                        default => 'primary',
                    }),
            ])
            ->defaultSort('id', 'desc');
    }

    protected function getHeaderActions(): array
    {
        $tenantTz = SystemConfig::get('sys_system_time_zone', 'Africa/Cairo');

        return [
            Action::make('create_reservation')
                ->label('حجز وقت جديد')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->form([
                    Select::make('branch_id')
                        ->label('الفرع')
                        ->options(fn (): array => Branch::withoutGlobalScope(BranchScope::class)->where('is_active', true)->pluck('name', 'id')->toArray())
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn ($set): mixed => $set('device_id', null)),
                    Select::make('device_id')
                        ->label('الجهاز')
                        ->options(function ($get): array {
                            $branchId = $get('branch_id');
                            if (! $branchId) {
                                return [];
                            }

                            return Devices::withoutGlobalScope(BranchScope::class)
                                ->where('branch_id', $branchId)
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->required()
                        ->searchable(),
                    DateTimePicker::make('start_time')
                        ->label('وقت وتاريخ الحجز')
                        ->required()
                        ->timezone($tenantTz)
                        ->default(now($tenantTz)->addMinutes(30))
                        ->minDate(now($tenantTz)),
                    TextInput::make('duration_minutes')
                        ->label('المدة المتوقعة (بالدقائق)')
                        ->numeric()
                        ->required()
                        ->default(60)
                        ->minValue(15),
                ])
                ->action(function (array $data) use ($tenantTz): void {
                    /** @var Customer|null $customer */
                    $customer = Auth::guard('customer')->user();
                    if (! $customer) {
                        return;
                    }

                    $startTime = Carbon::parse($data['start_time'], $tenantTz);
                    $duration = (int) $data['duration_minutes'];
                    $endTime = (clone $startTime)->addMinutes($duration);

                    // Check strict overlap
                    if (Reservation::checkOverlap((int) $data['device_id'], $startTime->toDateTimeString(), $endTime->toDateTimeString())) {
                        Notification::make()
                            ->title('عفواً، هذا الجهاز محجوز بالفعل في الفترة المحددة')
                            ->body('يرجى اختيار وقت آخر أو جهاز مختلف.')
                            ->danger()
                            ->send();

                        return;
                    }

                    Reservation::query()->create([
                        'branch_id' => (int) $data['branch_id'],
                        'device_id' => (int) $data['device_id'],
                        'customer_id' => $customer->id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'duration_minutes' => $duration,
                        'status' => 'active',
                    ]);

                    Notification::make()
                        ->title('تم إرسال طلب الحجز بنجاح!')
                        ->body("تم حجز الجهاز في تاريخ {$startTime->format('d/m/Y h:i A')}")
                        ->success()
                        ->send();

                    $this->redirect(static::getUrl());
                }),
        ];
    }
}
