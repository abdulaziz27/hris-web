<?php

namespace App\Filament\Resources\Leaves\Pages;

use App\Filament\Resources\Leaves\LeaveResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateLeave extends CreateRecord
{
    protected static string $resource = LeaveResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
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
