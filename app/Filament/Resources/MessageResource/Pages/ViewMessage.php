<?php

namespace App\Filament\Resources\MessageResource\Pages;

use App\Filament\Resources\MessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMessage extends ViewRecord
{
    protected static string $resource = MessageResource::class;

    // This ensures only managers/admins can see and use the Edit button on the View page itself
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => auth()->user()->hasRole('manager')),
        ];
    }
}
