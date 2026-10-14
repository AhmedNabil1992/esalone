<?php

namespace App\Filament\Resources\Tenant\Devices\Pages;

use App\Filament\Resources\Tenant\Devices\DeviceResource;
use App\Models\Tenant\Devices;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditDevice extends EditRecord
{
    protected static string $resource = DeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->before(function (DeleteAction $action, Devices $record): void {
                    $hasRelations = $record->invoices()->exists()
                        || $record->sessions()->exists()
                        || DB::table('play_session_details')->where('device_id', $record->id)->exists()
                        || DB::table('order_session_details')->where('device_id', $record->id)->exists();

                    if ($hasRelations) {
                        Notification::make()
                            ->title('لا يمكن حذف هذا الجهاز')
                            ->body('لا يمكن حذف الجهاز لأنه مرتبط بسجلات أو فواتير أو جلسات سابقة. يمكنك إلغاء تفعيله (غير مفعل) بدلاً من حذفه.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
