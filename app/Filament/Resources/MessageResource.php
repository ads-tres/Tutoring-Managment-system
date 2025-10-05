<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageResource\Pages;
use App\Models\Message;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    
    // Define canonical roles for checks
    protected const UNRESTRICTED_ROLES = ['admin', 'manager']; 

    protected static function getRoles(): array
    {
        $roles = Role::pluck('name', 'name')->toArray();
        $roles['all_registered_users'] = 'All Registered Users (Broadcast)';
        return $roles;
    }

   

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();
        
        $query->with(['sender', 'recipientUser']); 

        if ($user->hasRole(self::UNRESTRICTED_ROLES)) {
            return $query;
        }

        $userRoles = $user->getRoleNames()->toArray();
        $userRoles[] = 'all_registered_users';
        
        $query->where(function (Builder $q) use ($user, $userRoles) {
            $q->where('recipient_user_id', $user->id) 
              ->orWhereIn('recipient_target', $userRoles) 
              ->orWhere('sender_id', $user->id); 
        });

        return $query;
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $canSendToAnywhere = $user->hasRole(self::UNRESTRICTED_ROLES);
        $defaultRecipientRole = 'manager';

        return $form
            ->schema([
                // === 1. RESTRICTED USER RECIPIENT FIELDS (Hidden and Enforced) ===
                Forms\Components\Group::make([
                    Forms\Components\Hidden::make('recipient_type')->default('role'),
                    Forms\Components\Hidden::make('recipient_target')->default($defaultRecipientRole),
                    Forms\Components\Hidden::make('recipient_user_id')->default(null),
                        
                ])->visible(fn () => !$canSendToAnywhere)->columns(1),

                // === 2. UNRESTRICTED USER RECIPIENT FIELDS (Interactive) ===
                Forms\Components\Group::make([
                    Forms\Components\Fieldset::make('Recipient Selection')
                        ->schema([
                            Forms\Components\Radio::make('recipient_type')
                                ->label('Who is the message intended for?')
                                ->options([
                                    'role' => 'A predefined User Role/Group',
                                    'individual' => 'A specific Individual User',
                                ])
                                ->default('role') 
                                ->live() 
                                ->inline()
                                ->required(),
                        ])->columnSpanFull(),

                    Forms\Components\Select::make('recipient_target')
                        ->label('Select Target Role/Group')
                        ->options(self::getRoles()) 
                        ->required(fn (Get $get) => ($get('recipient_type') === 'role')) 
                        ->visible(fn (Get $get) => ($get('recipient_type') === 'role'))
                        ->placeholder('Select a predefined role or group')
                        ->columnSpanFull(),

                    Forms\Components\Select::make('recipient_user_id')
                        ->label('Search and Select Individual User')
                        ->nullable()
                        ->searchable() 
                        ->getSearchResultsUsing(fn (string $search) => 
                            User::where('name', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                        )
                        ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                        ->required(fn (Get $get) => ($get('recipient_type') === 'individual')) 
                        ->visible(fn (Get $get) => ($get('recipient_type') === 'individual'))
                        ->placeholder('Start typing a user\'s name to search')
                        ->columnSpanFull(),
                ])->visible(fn () => $canSendToAnywhere)->columns(1),

                // Common fields
                Forms\Components\TextInput::make('subject')
                    ->label('Subject')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('content')
                    ->label('Message Content')
                    ->required()
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ])->columns(1);
    }
    
    public static function infolist(Infolist $infolist): Infolist
    {
        $user = Auth::user();
        $isRestricted = !$user->hasRole(self::UNRESTRICTED_ROLES);

        // Schema for Restricted Users 
        $restrictedSchema = [
            Components\Section::make('')
                ->description(fn (Message $record) => "Sent on {$record->created_at->format('F j, Y, g:i A')}")
                ->schema([
                    Components\TextEntry::make('subject')
                        ->label('Subject')
                        ->size(Components\TextEntry\TextEntrySize::Large)
                        ->color('primary')
                        ->weight(FontWeight::ExtraBold), // Use a bold weight for subject

                    // The main content body
                    Components\TextEntry::make('content')
                        ->label('') // Hide label for clean reading
                        ->columnSpanFull()
                        ->html() // Corrected: Using html() to render the content as HTML
                        ->prose() // Ensures rich text/markdown is styled for readability
                        ->extraAttributes(['class' => 'p-6 mt-4 bg-white/5 dark:bg-gray-700/50 rounded-lg text-lg leading-relaxed']), 
                ])
                ->columns(1)
                ->extraAttributes([
                    // Beautiful styling to make the message stand out
                    'class' => 'bg-gray-100 dark:bg-gray-900 border-l-4 border-l-primary-600 shadow-xl',
                ]),
        ];

        // --- Schema for Unrestricted Users (Full Details) ---
        $unrestrictedSchema = [
            Components\Section::make('Message Metadata')
                ->schema([
                    Components\TextEntry::make('sender.name')->label('Sender'),
                    Components\TextEntry::make('recipient_type')->label('Recipient Type')->badge(),
                    Components\TextEntry::make('recipient_target')
                        ->label('Target Role')
                        ->formatStateUsing(fn ($state) => self::getRoles()[$state] ?? $state)
                        ->placeholder('N/A (Individual)'),
                    Components\TextEntry::make('recipientUser.name')
                        ->label('Recipient User')
                        ->placeholder('N/A (Role)'),
                    Components\TextEntry::make('created_at')->label('Sent On')->dateTime(),
                ])->columns(2),
                
            Components\Section::make('Message Content')
                ->schema([
                    Components\TextEntry::make('subject')->label('Subject')->size(Components\TextEntry\TextEntrySize::Large)->weight(FontWeight::Bold),
                    Components\TextEntry::make('content')
                        ->label('Content')
                        ->html() // Corrected: Using html() here too
                        ->prose()
                        ->columnSpanFull(),
                ])->columns(1),
        ];

        return $infolist
            // Select the schema based on the user's role
            ->schema(
                $isRestricted ? $restrictedSchema : $unrestrictedSchema
            );
    }
    
    public static function table(Table $table): Table
    {
        $user = Auth::user();
        $canEdit = $user->hasRole(self::UNRESTRICTED_ROLES);
        // Determine if the user can see restricted metadata columns
        $canViewRestrictedColumns = $canEdit; // Same restriction applies

        return $table
            ->columns([
                // === HIDDEN FOR NON-MANAGERS ===
                Tables\Columns\TextColumn::make('sender.name')
                    ->label('Sender')
                    ->sortable()
                    ->visible($canViewRestrictedColumns),

                Tables\Columns\TextColumn::make('recipient_target')
                    ->label('Target Role')
                    ->formatStateUsing(fn ($state) => self::getRoles()[$state] ?? $state) 
                    ->placeholder('N/A (Individual)')
                    ->sortable()
                    ->visible($canViewRestrictedColumns),

                Tables\Columns\TextColumn::make('recipient_user_id')
                    ->label('Recipient User')
                    ->getStateUsing(fn (Message $record) => $record->recipientUser?->name)
                    ->placeholder('N/A (Role)')
                    ->sortable(false)
                    ->visible($canViewRestrictedColumns), 

                // === VISIBLE FOR ALL USERS ===
                Tables\Columns\TextColumn::make('subject') 
                    ->searchable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('content')
                    ->limit(50),
                    
                // === HIDDEN FOR NON-MANAGERS (Timestamp is metadata) ===
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible($canViewRestrictedColumns),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Allow all users to view the message
                Tables\Actions\ViewAction::make(),
                
                // Only managers/admins can edit
                Tables\Actions\EditAction::make()
                    ->visible($canEdit),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible($canEdit),
                ]),
            ]);
    }
    
    public static function getRelations(): array { return []; }
    
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sender_id'] = Auth::id();
        
        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessages::route('/'),
            'create' => Pages\CreateMessage::route('/create'),
            'view' => Pages\ViewMessage::route('/{record}'),
            'edit' => Pages\EditMessage::route('/{record}/edit'),
        ];
    }
}
