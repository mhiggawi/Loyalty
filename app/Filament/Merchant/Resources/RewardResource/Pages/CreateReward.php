<?php

namespace App\Filament\Merchant\Resources\RewardResource\Pages;

use App\Filament\Merchant\Resources\RewardResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreateReward extends CreateRecord
{
    protected static string $resource = RewardResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = Filament::getTenant()->id;
        $data['total_redemptions'] = 0;
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
