<?php

namespace App\Filament\Merchant\Resources\RewardResource\Pages;

use App\Filament\Merchant\Resources\RewardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReward extends EditRecord
{
    protected static string $resource = RewardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
