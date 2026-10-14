<?php

namespace App\Filament\Resources\Tenant\SalonServices\Schemas;

use App\Models\Tenant\CatalogItem;
use App\Models\Tenant\SalonService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SalonServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم الخدمة')
                    ->maxLength(120)
                    ->required(),
                Select::make('category')
                    ->label('التصنيف')
                    ->options([
                        'hair' => 'الشعر',
                        'barber' => 'الحلاقة',
                        'skin' => 'العناية بالبشرة',
                        'nails' => 'العناية بالأظافر',
                        'makeup' => 'المكياج',
                        'other' => 'أخرى',
                    ])
                    ->native(false)
                    ->searchable(),
                Textarea::make('description')
                    ->label('وصف الخدمة')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('duration_minutes')
                    ->label('المدة بالدقائق')
                    ->integer()
                    ->minValue(1)
                    ->maxValue(1440)
                    ->required(),
                TextInput::make('price')
                    ->label('السعر')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('ج.م')
                    ->required(),
                Section::make('الخامات المستهلكة')
                    ->description('تُخصم الكميات المحددة من المخزون عند تحصيل الموعد، ولا تُضاف كقيمة منفصلة على الفاتورة.')
                    ->schema([
                        Repeater::make('component_usages')
                            ->label('الخامات لكل تنفيذ للخدمة')
                            ->schema([
                                Select::make('component_id')
                                    ->label('الخامة')
                                    ->options(fn (): array => CatalogItem::query()
                                        ->where('is_component', true)
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->required(),
                                TextInput::make('quantity')
                                    ->label('الكمية لكل خدمة')
                                    ->numeric()
                                    ->minValue(0.001)
                                    ->step(0.001)
                                    ->required(),
                                TextInput::make('notes')
                                    ->label('ملاحظات')
                                    ->maxLength(100),
                            ])
                            ->columns(3)
                            ->maxItems(30)
                            ->reorderable(false)
                            ->addActionLabel('إضافة خامة')
                            ->afterStateHydrated(function (Repeater $component, ?SalonService $record): void {
                                if (! $record?->catalogItem) {
                                    $component->state([]);

                                    return;
                                }

                                $component->state(
                                    $record->catalogItem
                                        ->components()
                                        ->orderBy('sort_order')
                                        ->get()
                                        ->map(fn ($componentUsage): array => [
                                            'component_id' => $componentUsage->component_id,
                                            'quantity' => $componentUsage->quantity,
                                            'notes' => $componentUsage->notes,
                                        ])
                                        ->all(),
                                );
                            }),
                    ])
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('متاحة للحجز')
                    ->default(true)
                    ->required(),
            ]);
    }
}
