<?php

namespace App\Filament\Merchant\Resources\PointsSettingResource\Pages;

use App\Filament\Merchant\Resources\PointsSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Facades\Filament;

class ManagePointsSettings extends ManageRecords
{
    protected static string $resource = PointsSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['tenant_id'] = Filament::getTenant()->id;
                    return $data;
                }),
        ];
    }
}
