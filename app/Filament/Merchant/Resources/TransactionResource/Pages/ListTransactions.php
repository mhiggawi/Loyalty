<?php

namespace App\Filament\Merchant\Resources\TransactionResource\Pages;

use App\Filament\Merchant\Resources\TransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;
}
