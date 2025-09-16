<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'students';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('parent_id')
                    ->relationship('parent', 'name')
                    ->required(),
                Forms\Components\TextInput::make('full_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('student_phone')
                    ->tel()
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Select::make('sex')
                    ->options([
                        'M' => 'Male',
                        'F' => 'Female',
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('dob'),
                Forms\Components\Textarea::make('initial_skills')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('father_name')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('father_phone')
                    ->tel()
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('mother_name')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('mother_phone')
                    ->tel()
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('region')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('city')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('subcity')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('district')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('kebele')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('house_number')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('street')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('landmark')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('school_name')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Select::make('school_type')
                    ->options([
                        'private' => 'Private',
                        'public' => 'Public',
                        'international' => 'International',
                    ]),
                Forms\Components\TextInput::make('grade')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('frequency')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\CheckboxList::make('scheduled_days')
                    ->options([
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                        'sunday' => 'Sunday',
                    ]),
                Forms\Components\TimePicker::make('start_time'),
                Forms\Components\TimePicker::make('end_time'),
                Forms\Components\TextInput::make('session_duration')
                    ->numeric()
                    ->suffix('minutes')
                    ->default(null),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'on hold' => 'On Hold',
                    ])
                    ->default('active')
                    ->required(),
                Forms\Components\DatePicker::make('start_date'),
                Forms\Components\FileUpload::make('student_image')
                    ->image(),
                Forms\Components\FileUpload::make('parents_image')
                    ->image(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student_phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sex'),
                Tables\Columns\TextColumn::make('dob')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'on hold' => 'warning',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
