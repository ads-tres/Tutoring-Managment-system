<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TimePicker;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
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
                Forms\Components\TextInput::make('sex')
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
                Forms\Components\TextInput::make('school_type'),
                Forms\Components\TextInput::make('grade')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('frequency')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('start_time'),
                Forms\Components\TextInput::make('end_time'),
                Forms\Components\TextInput::make('session_duration')
                    ->numeric()
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


                Forms\Components\CheckboxList::make('scheduled_days')
                    ->label('Weekly schedule')
                    ->options([
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                        'sunday' => 'Sunday',
                    ])
                    ->columns(3),

                Forms\Components\TimePicker::make('start_time')
                    ->seconds(false),

                Forms\Components\TextInput::make('session_length_minutes')
                    ->numeric()
                    ->minValue(30)
                    ->step(15)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('parent_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('student_phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sex'),
                Tables\Columns\TextColumn::make('dob')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('father_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('father_phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('mother_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('mother_phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('region')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('city')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subcity')
                    ->searchable(),
                Tables\Columns\TextColumn::make('district')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('kebele')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('house_number')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('street')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('landmark')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('school_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('school_type'),
                Tables\Columns\TextColumn::make('grade')
                    ->searchable(),
                Tables\Columns\TextColumn::make('frequency')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('start_time')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('end_time')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('session_duration')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'on hold' => 'warning',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ImageColumn::make('student_image'),
                Tables\Columns\ImageColumn::make('parents_image')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'on hold' => 'On Hold',
                    ]),
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\AttendancesRelationManager::class,
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
