<?php

namespace App\Filament\Resources\AccountantResource\Pages;

use App\Filament\Resources\AccountantResource;
use Filament\Resources\Pages\ListRecords;

class ListAccountants extends ListRecords
{
    // This tells the page which resource it belongs to.
    protected static string $resource = AccountantResource::class;

    protected function getHeaderActions(): array
    {
        // Leaving this empty as it's an overview, not a creation page.
        return [];
    }
}
