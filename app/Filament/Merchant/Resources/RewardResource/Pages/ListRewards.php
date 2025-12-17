<?php

namespace App\Filament\Merchant\Resources\RewardResource\Pages;

use App\Filament\Merchant\Resources\RewardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRewards extends ListRecords
{
    protected static string $resource = RewardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }
}
