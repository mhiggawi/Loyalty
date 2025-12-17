<?php

namespace App\Filament\Merchant\Resources\StaffResource\Pages;

use App\Filament\Merchant\Resources\StaffResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = Filament::getTenant()->id;

        // Set default permissions array if not set
        if (!isset($data['permissions'])) {
            $data['permissions'] = [];
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
