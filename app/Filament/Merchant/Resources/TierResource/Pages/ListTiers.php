<?php

namespace App\Filament\Merchant\Resources\TierResource\Pages;

use App\Filament\Merchant\Resources\TierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTiers extends ListRecords
{
    protected static string $resource = TierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }
}
