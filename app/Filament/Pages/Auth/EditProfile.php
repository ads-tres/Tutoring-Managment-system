<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action; 
use Illuminate\Support\Facades\Route; 

/**
 * Custom EditProfile page to override default Filament functionality.
 * Adds custom fields and explicitly defines the "Return to dashboard" button.
 */
class EditProfile extends BaseEditProfile
{
    // Override the navigation label that appears in the user dropdown
    protected static ?string $navigationLabel = 'My Settings';

    // Override the title displayed on the page
    protected static ?string $title = 'Update Your User Profile';

    /**
     * The correct method to override for customizing the form fields.
     */
    protected function getFormSchema(): array
    {
        // 1. Get the default fields (Name, Email, Password)
        $parentSchema = parent::getFormSchema();

        // 2. Define any new custom fields
        $customFields = [
            TextInput::make('timezone')
                ->label('Timezone Preference')
                ->placeholder('e.g., America/New_York')
                ->helperText('This field is for demonstration purposes.')
                ->maxLength(255),
        ];

        // 3. Merge the parent fields with the custom fields
        return array_merge(
            $parentSchema,
            $customFields
        );
    }

    /**
     * Override this method to define actions (buttons) that appear in the page header.
     * This adds the "Return to dashboard" button.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('dashboard')
                ->label(__('filament-panels::pages/dashboard.title')) // Use Filament's default "Dashboard" label
                ->icon('heroicon-o-home') 
                // Uses the standard Laravel route helper to get the dashboard URL
                ->url(fn (): string => route('filament.admin.dashboard')) 
                ->color('gray'), 
        ];
    }
}
