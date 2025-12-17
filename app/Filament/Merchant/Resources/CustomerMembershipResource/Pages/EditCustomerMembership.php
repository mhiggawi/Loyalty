<?php

namespace App\Filament\Merchant\Resources\CustomerMembershipResource\Pages;

use App\Filament\Merchant\Resources\CustomerMembershipResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerMembership extends EditRecord
{
    protected static string $resource = CustomerMembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No delete action for customer memberships
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
