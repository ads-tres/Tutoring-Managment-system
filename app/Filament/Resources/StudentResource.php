<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers\AttendancesRelationManager;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Students Management';
    
    /**
     * Filters students based on the logged-in user's role.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        // If the logged-in user is a parent, only show their own children.
        if ($user->hasRole('parent')) {
            return Student::query()->where('parent_id', $user->id);
        }

        // Managers, tutors, and other roles see all students.
        return Student::query();
    }
    
    /**
     * Defines the form schema for the student resource.
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Student Details')
                ->schema([
                    Forms\Components\TextInput::make('full_name')->required()->maxLength(255),
                    Forms\Components\TextInput::make('student_phone')->tel()->maxLength(20),
                    Forms\Components\DatePicker::make('dob')->label('Date of Birth')->native(false),
                    Forms\Components\Select::make('sex')
                        ->options([
                            'M' => 'Male',
                            'F' => 'Female',
                        ])
                        ->required(),
                    Forms\Components\FileUpload::make('student_image')
                        ->label('Student Image')
                        ->image()
                        ->disk('public')
                        ->directory('student-images')
                        ->visibility('public')
                        ->avatar(),
                    Forms\Components\TagsInput::make('initial_skills')
                        ->label('Initial Skills')
                        ->placeholder('Add initial skills here')
                        ->separator(','),
                ])->columns(2),

            Forms\Components\Section::make('Parent Information')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Select::make('parent_id')
                        ->relationship('parent', 'name')
                        ->label('Parent Account')
                        ->searchable()
                        ->visible(fn() => Auth::user()->hasRole('manager')),
                    Forms\Components\FileUpload::make('parents_image')
                        ->label('Parents\' Image')
                        ->image()
                        ->disk('public')
                        ->directory('parent-images')
                        ->visibility('public')
                        ->avatar(),
                    Forms\Components\TextInput::make('father_name')->maxLength(255),
                    Forms\Components\TextInput::make('father_phone')->tel()->maxLength(20),
                    Forms\Components\TextInput::make('mother_name')->maxLength(255),
                    Forms\Components\TextInput::make('mother_phone')->tel()->maxLength(20),
                ])->columns(2),

            Forms\Components\Section::make('Address')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('region')->maxLength(255),
                    Forms\Components\TextInput::make('city')->maxLength(255),
                    Forms\Components\TextInput::make('subcity')->maxLength(255),
                    Forms\Components\TextInput::make('district')->maxLength(255),
                    Forms\Components\TextInput::make('kebele')->maxLength(255),
                    Forms\Components\TextInput::make('house_number')->maxLength(255),
                    Forms\Components\TextInput::make('street')->maxLength(255),
                    Forms\Components\TextInput::make('landmark')->maxLength(255),
                ])->columns(2),

            Forms\Components\Section::make('School & Session Details')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('school_name')->maxLength(255),
                    Forms\Components\Select::make('school_type')
                        ->options([
                            'private' => 'Private',
                            'public' => 'Public',
                            'international' => 'International',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('grade')->numeric(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                        ])
                        ->default('active'),
                    Forms\Components\TextInput::make('frequency')->label('Session Frequency')->numeric()->hint('e.g. Number of times per week'),
                    Forms\Components\DatePicker::make('start_date')->native(false),
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
                    Forms\Components\TimePicker::make('start_time')->native(false),
                    Forms\Components\TimePicker::make('end_time')->native(false),
                    Forms\Components\TextInput::make('session_length_minutes')->label('Session Length (minutes)')->numeric(),
                    Forms\Components\TextInput::make('session_duration')->label('Session Duration')->hint('e.g. 1 hour, 30 minutes'),
                ])->columns(2),
        ]);
    }

    /**
     * Defines the table schema for the student resource.
     */
    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('student_image')->label('Student Image')->circular()->defaultImageUrl(url('/images/placeholder.svg'))->toggleable(),
                Tables\Columns\ImageColumn::make('parents_image')->label('Parents\' Image')->circular()->defaultImageUrl(url('/images/placeholder.svg'))->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('full_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('parent.name')->label('Parent')->sortable()->visible(fn() => Auth::user()->hasRole('manager'))->toggleable(),
                Tables\Columns\TextColumn::make('student_phone')->label('Phone')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sex')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('dob')->label('Date of Birth')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('initial_skills')->label('Initial Skills')->badge()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('father_name')->label('Father')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('mother_name')->label('Mother')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('region')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('city')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('subcity')->label('Sub-city')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('district')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('kebele')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('house_number')->label('House #')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('street')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('landmark')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('school_name')->searchable()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('school_type')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('grade')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('frequency')->label('Frequency')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('start_time')->label('Start Time')->time('H:i')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('end_time')->label('End Time')->time('H:i')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('session_length_minutes')->label('Session Length (min)')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('session_duration')->label('Session Duration')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('scheduled_days')->label('Scheduled Days')->badge()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('start_date')->label('Start Date')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')->badge()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
                Tables\Filters\SelectFilter::make('school_type')
                    ->options([
                        'private' => 'Private',
                        'public' => 'Public',
                        'international' => 'International',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    
    /**
     * Defines the relations for the student resource.
     */
    public static function getRelations(): array
    {
        return [
            AttendancesRelationManager::class,
        ];
    }
    
    /**
     * Defines the pages for the student resource.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
            'view' => Pages\ViewStudent::route('/{record}'),
        ];
    }
}
