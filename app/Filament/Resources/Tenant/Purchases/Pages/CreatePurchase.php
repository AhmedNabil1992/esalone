<?php

namespace App\Filament\Resources\Tenant\Purchases\Pages;

use App\Actions\Tenant\PostPurchaseAction;
use App\Filament\Resources\Tenant\Purchases\PurchaseResource;
use App\Models\Tenant\Shift;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(PostPurchaseAction::class)->execute([
            'shift_id' => Shift::currentOpenShiftId(),
            'warehouse_id' => (int) $data['warehouse_id'],
            'cash_box_id' => (int) $data['cash_box_id'],
            'supplier_id' => filled($data['supplier_id'] ?? null) ? (int) $data['supplier_id'] : null,
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'posted',
            'purchased_at' => now()->toDateTimeString(),
            'items' => $data['items'] ?? [],
        ], Auth::user());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
