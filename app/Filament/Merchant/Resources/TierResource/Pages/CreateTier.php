<?php

namespace App\Filament\Merchant\Resources\TierResource\Pages;

use App\Filament\Merchant\Resources\TierResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreateTier extends CreateRecord
{
    protected static string $resource = TierResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = Filament::getTenant()->id;
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
