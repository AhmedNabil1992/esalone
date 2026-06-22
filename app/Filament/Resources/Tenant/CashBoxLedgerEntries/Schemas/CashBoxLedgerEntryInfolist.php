<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenant\CashBoxLedgerEntries\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CashBoxLedgerEntryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('cashBox.name')
                    ->label('الخزنة'),
                TextEntry::make('direction')
                    ->label('الاتجاه')
                    ->formatStateUsing(fn (string $state): string => $state === 'in' ? 'دخول' : 'خروج')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'in' ? 'success' : 'danger'),
                TextEntry::make('operation')
                    ->label('نوع العملية')
                    ->formatStateUsing(fn (string $state): string => self::formatOperation($state))
                    ->badge(),
                TextEntry::make('reference_type')
                    ->label('مرجع العملية')
                    ->formatStateUsing(fn (?string $state, $record): string => self::formatReference($state, $record->reference_id))
                    ->placeholder('-'),
                TextEntry::make('amount')
                    ->label('المبلغ')
                    ->money(),
                TextEntry::make('shift.shift_no')
                    ->label('رقم الشيفت')
                    ->placeholder('-'),
                TextEntry::make('createdBy.name')
                    ->label('بواسطة')
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->label('الوصف')
                    ->placeholder('-'),
                TextEntry::make('occurred_at')
                    ->label('تاريخ العملية')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->label('وقت التسجيل')
                    ->dateTime(),
            ]);
    }

    private static function formatOperation(string $operation): string
    {
        return match ($operation) {
            'purchase' => 'شراء',
            'purchase_reverse' => 'عكس شراء',
            'expense' => 'مصروف',
            'customer_credit_deposit' => 'إيداع رصيد عميل',
            'customer_credit_withdraw' => 'سحب رصيد عميل',
            'adjustment' => 'تسوية',
            'transfer' => 'تحويل',
            default => $operation,
        };
    }

    private static function formatReference(?string $referenceType, ?int $referenceId): string
    {
        if (! $referenceType || ! $referenceId) {
            return '-';
        }

        $normalizedReferenceType = str_replace('/', '\\', ltrim($referenceType, '\\'));
        $referenceBaseName = strtolower(class_basename($normalizedReferenceType));

        $referenceLabel = match ($referenceBaseName) {
            'purchase' => 'شراء',
            'expensehistory' => 'مصروف',
            'customercredit' => 'رصيد عميل',
            'invoice' => 'فاتورة',
            'invoicepayment' => 'دفعة فاتورة',
            'cashbox' => 'خزنة',
            'cashboxledgerentry' => 'قيد خزنة',
            'stockmovement' => 'حركة مخزون',
            default => 'مرجع',
        };

        return sprintf('%s #%d', $referenceLabel, $referenceId);
    }
}
