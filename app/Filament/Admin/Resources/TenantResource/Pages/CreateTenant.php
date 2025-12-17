<?php

namespace App\Filament\Admin\Resources\TenantResource\Pages;

use App\Filament\Admin\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate API key
        $data['api_key'] = Str::random(40);

        // Set default trial end date if on trial
        if ($data['subscription_status'] === 'trial' && !isset($data['trial_ends_at'])) {
            $data['trial_ends_at'] = now()->addDays(14);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
