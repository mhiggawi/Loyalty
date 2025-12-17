<?php

namespace App\Filament\Merchant\Resources\RedemptionResource\Pages;

use App\Filament\Merchant\Resources\RedemptionResource;
use Filament\Resources\Pages\EditRecord;

class EditRedemption extends EditRecord
{
    protected static string $resource = RedemptionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
