<?php

namespace App\Filament\Resources\MessageResource\Pages;

use App\Filament\Resources\MessageResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateMessage extends CreateRecord
{
    protected static string $resource = MessageResource::class;

    /**
     * Mutates the form data before creating the record to inject the sender_id
     * and clean up the recipient fields based on the chosen recipient_type.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 1. Inject the mandatory sender_id (This is necessary as sender_id is not on the form)
        $data['sender_id'] = Auth::id();

        // 2. Clean up recipient fields based on the form's temporary 'recipient_type'
        // This ensures the unused column is explicitly set to NULL for data integrity.
        if ($data['recipient_type'] === 'individual') {
            
            // If sending to an individual user, ensure role target is null
            $data['recipient_target'] = null;

        } else { // 'role' is selected
            
            // If sending to a role, ensure individual user ID is null
            $data['recipient_user_id'] = null;
        }

        // 3. Remove the temporary control field before saving to the database
        unset($data['recipient_type']);
        
        return $data;
    }
}
