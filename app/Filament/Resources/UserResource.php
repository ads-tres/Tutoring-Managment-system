<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Resources\UserResource\Pages;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\UserResource\RelationManagers\StudentsRelationManager;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Administration';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->label('First Name')->required(),
                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required(fn(string $operation): bool => $operation === 'create')
                    ->dehydrateStateUsing(fn(string $state): string => filled($state) ? Hash::make($state) : $state)
                    ->dehydrated(fn(?string $state): bool => filled($state))
                    ->autocomplete('password')
                    ->rule(
                        fn(string $operation, Get $get) =>
                        $operation === 'create' || filled($get('password')) ?
                        'min:8' : ''
                    )
                    ->live(),

                // CONFIRM PASSWORD FIELD 
                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Confirm Password')
                    ->password()
                    ->required(fn(string $operation, Get $get): bool => $operation === 'create' || filled($get('password')))
                    ->same('password')
                    ->dehydrated(false)
                    ->autocomplete('password'),
                Forms\Components\TextInput::make('middle_name')->label('Middle Name'),
                Forms\Components\TextInput::make('last_name')->label('Last Name')->required(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(User::class, 'email', ignoreRecord: true),
                Forms\Components\TextInput::make('phone')->label('Phone'),
                Forms\Components\DatePicker::make('date_of_birth')->label('Date of Birth'),
                Forms\Components\FileUpload::make('profile_photo_path')->label('Profile Photo')->image(),

                // Role assignment using Shield & Spatie
                Forms\Components\Select::make('roles')
                    ->label('Roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->helperText('Assign one or more roles'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('Roll No.')->label('Roll No.')->rowIndex(),
                Tables\Columns\TextColumn::make('name')->label('First Name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('roles')
                    ->label('Roles')
                    ->formatStateUsing(fn($state, $record) => $record->roles->pluck('name')->join(', ')),
            ])
            ->actions(actions: [EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            StudentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
