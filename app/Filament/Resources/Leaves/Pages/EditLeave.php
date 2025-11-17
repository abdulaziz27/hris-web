<?php

namespace App\Filament\Resources\Leaves\Pages;

use App\Filament\Resources\Leaves\LeaveResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLeave extends EditRecord
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Hapus'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Allow total_days = 0 (user might want to submit cuti for weekend/holiday)
        // Just show warning but don't block
        if (isset($data['total_days']) && $data['total_days'] <= 0) {
            Notification::make()
                ->title('Peringatan')
                ->body('Total hari cuti = 0. Pastikan ini sesuai kebutuhan. Jika perlu, edit manual total hari cuti.')
                ->warning()
                ->send();
        }

        return $data;
    }
}
