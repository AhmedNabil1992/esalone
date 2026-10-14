<?php

namespace App\Filament\Resources\Tenant\StaffMembers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StaffMemberInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('الاسم'),
                TextEntry::make('job_title')
                    ->label('التخصص')
                    ->placeholder('—'),
                TextEntry::make('phone')
                    ->label('الهاتف')
                    ->placeholder('—'),
                TextEntry::make('email')
                    ->label('البريد الإلكتروني')
                    ->placeholder('—'),
                TextEntry::make('commission_rate')
                    ->label('نسبة العمولة')
                    ->suffix('%'),
                TextEntry::make('weekly_schedule')
                    ->label('الدوام الأسبوعي')
                    ->formatStateUsing(function (?array $state): string {
                        if (! $state) {
                            return 'غير محدد';
                        }

                        $days = [
                            'الأحد',
                            'الاثنين',
                            'الثلاثاء',
                            'الأربعاء',
                            'الخميس',
                            'الجمعة',
                            'السبت',
                        ];

                        return collect($state)
                            ->map(fn (array $slot): string => sprintf(
                                '%s %s–%s',
                                $days[(int) $slot['day_of_week']] ?? '—',
                                $slot['opens_at'] ?? '',
                                $slot['closes_at'] ?? '',
                            ))
                            ->implode('، ');
                    })
                    ->columnSpanFull(),
                IconEntry::make('is_active')
                    ->label('يستقبل الحجوزات')
                    ->boolean(),
            ]);
    }
}
