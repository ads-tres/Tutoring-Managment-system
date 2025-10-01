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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static function getRoles(): array
    {
        $roles = Role::pluck('name', 'name')->toArray();
        $roles['all_registered_users'] = 'All Registered Users (Broadcast)';
        return $roles;
    }

    /**
     * Scope the query and EAGER LOADS the sender and recipientUser relationships.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();
        
        // CRITICAL: Eager load relationships for the table columns (sender and recipientUser).
        $query->with(['sender', 'recipientUser']); 

        // Admins and Managers can see all messages
        if ($user->hasRole(['Manager', 'admin'])) {
            return $query;
        }

        // Determine all roles the user belongs to, including the broadcast 'all_registered_users'
        $userRoles = $user->getRoleNames()->toArray();
        $userRoles[] = 'all_registered_users';
        
        $query->where(function (Builder $q) use ($user, $userRoles) {
            $q->where('recipient_user_id', $user->id) // Addressed to the user individually
              ->orWhereIn('recipient_target', $userRoles) // Addressed to a role the user has
              ->orWhere('sender_id', $user->id); // Sent by the user
        });

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->nullable()
                    ->required(fn (Get $get) => $get('recipient_type') === 'role') 
                    ->visible(fn (Get $get) => $get('recipient_type') === 'role')
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
                    ->required(fn (Get $get) => $get('recipient_type') === 'individual') 
                    ->visible(fn (Get $get) => $get('recipient_type') === 'individual')
                    ->placeholder('Start typing a user\'s name to search')
                    ->columnSpanFull(),

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
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sender.name')
                    ->label('Sender')
                    ->sortable(),
                Tables\Columns\TextColumn::make('recipient_target')
                    ->label('Target Role')
                    ->formatStateUsing(fn ($state) => self::getRoles()[$state] ?? $state) 
                    ->placeholder('N/A (Individual)')
                    ->sortable(),
                
                // FIX: Use the base ID column and explicitly retrieve the user name
                // using the eagerly loaded 'recipientUser' relationship.
                Tables\Columns\TextColumn::make('recipient_user_id')
                    ->label('Recipient User')
                    ->getStateUsing(fn (Message $record) => $record->recipientUser?->name)
                    ->placeholder('N/A (Role)')
                    ->sortable(false), // Sorting is disabled for closure-based content

                Tables\Columns\TextColumn::make('subject') 
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('content')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    
    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessages::route('/'),
            'create' => Pages\CreateMessage::route('/create'),
            'edit' => Pages\EditMessage::route('/{record}/edit'),
        ];
    }
}
