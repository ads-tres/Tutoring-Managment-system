<?php

namespace App\Filament\Resources\MessageResource\Pages;

use App\Filament\Resources\MessageResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth; // <-- IMPORTANT: Import the Auth facade

class CreateMessage extends CreateRecord
{
    protected static string $resource = MessageResource::class;

    /**
     * This method is called before the record is created. 
     * We use it to inject the ID of the currently authenticated user 
     * as the sender_id.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Get the ID of the currently logged-in user
        $data['sender_id'] = Auth::id();

        // If for some reason Auth::id() is null (e.g., if you have guest access), 
        // you might need a fallback or an error check here, but for an admin panel 
        // running inside Filament, Auth::id() should always work.
        
        return $data;
    }
}
