<?php

namespace App\Filament\Merchant\Resources\TierResource\Pages;

use App\Filament\Merchant\Resources\TierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTier extends EditRecord
{
    protected static string $resource = TierResource::class;

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
