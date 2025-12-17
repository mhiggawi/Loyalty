<?php

namespace App\Filament\Merchant\Resources\CustomerMembershipResource\Pages;

use App\Filament\Merchant\Resources\CustomerMembershipResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerMemberships extends ListRecords
{
    protected static string $resource = CustomerMembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - customers register via mobile app
        ];
    }
}
