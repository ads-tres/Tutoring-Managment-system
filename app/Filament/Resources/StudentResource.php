<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers\AttendancesRelationManager;
use App\Models\Student;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Closure;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;


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

        // If the logged-in user is a tutor, only show students assigned to them.
        if ($user->hasRole('tutor')) {
            return Student::query()->where('tutor_id', $user->id);
        }

        // Managers, and other roles see all students.
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

            Forms\Components\Section::make('Parent & Tutor Information')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Select::make('parent_id')
                        ->relationship('parent', 'name')
                        ->label('Parent Account')
                        ->searchable()
                        ->visible(fn() => Auth::user()->hasRole('manager')),
                    Forms\Components\Select::make('tutor_id')
                        ->relationship('tutor', 'name', fn (Builder $query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'tutor')))
                        ->label('Tutor Account')
                        ->searchable()
                        ->preload()
                        ->native(false)
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
                        ->default('active')
                        ->visible(fn() => Auth::user()->hasRole('manager')),
                    Forms\Components\TextInput::make('payment_status')
                        ->maxLength(255)
                        ->visible(fn() => Auth::user()->hasRole('manager')),
                    Forms\Components\TextInput::make('frequency')
                        ->label('Frequency (sessions per week)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(7)
                        ->live()
                        ->required(),
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
                        ])
                        ->required()
                        ->rules([
                            fn (Forms\Get $get): Closure =>
                            function (string $attribute, $value, Closure $fail) use ($get) {
                                if (count($value) !== (int) $get('frequency')) {
                                    $fail("You must select exactly {$get('frequency')} days.");
                                }
                            },
                        ])
                        ->columns(3),
                    Forms\Components\TimePicker::make('start_time')->native(false),
                    Forms\Components\TimePicker::make('end_time')->native(false),
                    Forms\Components\TextInput::make('session_length_minutes')->label('Session Length (minutes)')->numeric(),
                    Forms\Components\TextInput::make('session_duration')->label('Session Duration')->hint('e.g. 1 hour, 30 minutes'),
                    TextInput::make('sessions_per_period')
                        ->label('Sessions Per Period')
                        ->numeric()
                        ->required(),
                    TextInput::make('price_per_session')
                        ->label('Price Per Session')
                        ->numeric()
                        ->required(),
                ])->columns(2),
                TextInput::make('sessions_per_period')
                ->numeric()
                ->default(12)
                ->label('Sessions Per Period'),

            TextInput::make('price_per_session')
                ->numeric()
                ->prefix('ETB')
                ->default(0)
                ->label('Price Per Session'),
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
                Tables\Columns\TextColumn::make('tutor.name')->label('Tutor')->sortable()->toggleable(),
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
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn() => !Auth::user()->hasRole('tutor')),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn() => !Auth::user()->hasRole('tutor')),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('period_total')
                ->label('Period Total')
                ->money('ETB'),

                Tables\Columns\TextColumn::make('unpaid_sessions_count')
                ->label('Unpaid Sessions'),

                Tables\Columns\TextColumn::make('total_due')
                ->label('Total Due')
                ->money('ETB'),

                Tables\Columns\TextColumn::make('total_completed_sessions')
                ->label('Total Completed Sessions'),
                
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->visible(fn() => !Auth::user()->hasRole('tutor')),
                Tables\Filters\SelectFilter::make('school_type')
                    ->options([
                        'private' => 'Private',
                        'public' => 'Public',
                        'international' => 'International',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()->visible(fn() => !Auth::user()->hasRole('tutor')),
                Tables\Actions\DeleteAction::make()->visible(fn() => !Auth::user()->hasRole('parent') && !Auth::user()->hasRole('tutor')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->visible(fn() => !Auth::user()->hasRole('parent') && !Auth::user()->hasRole('tutor')),
                ]),
            ])
            ->headerActions([
                // Tutor "Fill Daily Attendance" Action (kept for tutors)
                Tables\Actions\Action::make('fill_daily_attendance')
                    ->label('Fill Daily Attendance')
                    ->icon('heroicon-o-document-check')
                    ->visible(fn() => Auth::user()->hasRole('tutor') && !Attendance::where('tutor_id', Auth::user()->id)->whereDate('created_at', Carbon::today())->exists())
                    ->form(function (Tables\Actions\Action $action) {
                        $user = Auth::user();
                        $today = strtolower(Carbon::now()->format('l'));

                        $studentsForToday = Student::where('tutor_id', $user->id)
                            ->whereJsonContains('scheduled_days', $today)
                            ->orderBy('start_time')
                            ->get();

                        if ($studentsForToday->isEmpty()) {
                            Notification::make()
                                ->title('No Sessions Today')
                                ->body('You have no students with sessions scheduled for today that require attendance.')
                                ->warning()
                                ->send();

                            return [];
                        }

                        $steps = $studentsForToday->map(function ($student, $index) use ($user) {
                            return Forms\Components\Wizard\Step::make("{$student->full_name} ({$student->start_time})")
                                ->schema([
                                    Forms\Components\Select::make("students.{$index}.session_status")
                                        ->label('Session Status')
                                        ->options([
                                            'present' => 'Present',
                                            'absent' => 'Absent',
                                            'late' => 'Late',
                                        ])
                                        ->default('present')
                                        ->live()
                                        ->required(),

                                    Forms\Components\Select::make("students.{$index}.type")
                                        ->label('Session Type')
                                        ->options([
                                            'on-schedule' => 'On-Schedule',
                                            'rescheduled' => 'Rescheduled',
                                            'additional' => 'Additional Session',
                                        ])
                                        ->default('on-schedule')
                                        ->live()
                                        ->visible(fn(Forms\Get $get) => $get("students.{$index}.session_status") !== 'absent')
                                        ->required(fn(Forms\Get $get) => $get("students.{$index}.session_status") !== 'absent'),

                                    Forms\Components\DatePicker::make("students.{$index}.scheduled_date")
                                        ->label('Scheduled Date')
                                        ->native(false)
                                        ->default(Carbon::now())
                                        ->disabled(fn(Forms\Get $get) => $get("students.{$index}.type") === 'on-schedule' || $get("students.{$index}.session_status") === 'absent')
                                        ->visible(fn(Forms\Get $get) => $get("students.{$index}.session_status") !== 'absent'),

                                    Forms\Components\DatePicker::make("students.{$index}.actual_date")
                                        ->label('Actual Date')
                                        ->native(false)
                                        ->default(Carbon::now())
                                        ->visible(fn(Forms\Get $get) => in_array($get("students.{$index}.type"), ['rescheduled', 'additional']) && $get("students.{$index}.session_status") !== 'absent')
                                        ->required(fn(Forms\Get $get) => in_array($get("students.{$index}.type"), ['rescheduled', 'additional']) && $get("students.{$index}.session_status") !== 'absent'),

                                    Forms\Components\TextInput::make("students.{$index}.subject")
                                        ->label('Subject')
                                        ->placeholder('e.g., Math, Science, English')
                                        ->visible(fn(Forms\Get $get) => $get("students.{$index}.session_status") !== 'absent')
                                        ->required(fn(Forms\Get $get) => $get("students.{$index}.session_status") !== 'absent'),

                                    Forms\Components\TextInput::make("students.{$index}.topic")
                                        ->label('Topic Covered')
                                        ->placeholder('e.g., Algebra, Chapter 3: Functions')
                                        ->visible(fn(Forms\Get $get) => $get("students.{$index}.session_status") !== 'absent')
                                        ->required(fn(Forms\Get $get) => $get("students.{$index}.session_status") !== 'absent'),

                                    Forms\Components\TextInput::make("students.{$index}.duration")
                                        ->label('Duration')
                                        ->numeric()
                                        ->placeholder('e.g., 2')
                                        ->hint('Duration in hours.')
                                        ->visible(fn(Forms\Get $get) => $get("students.{$index}.session_status") !== 'absent')
                                        ->required(fn(Forms\Get $get) => $get("students.{$index}.session_status") !== 'absent'),

                                    Forms\Components\TextInput::make("students.{$index}.reason")
                                        ->label('Reason')
                                        ->placeholder('Enter reason for rescheduling or additional session...')
                                        ->visible(fn(Forms\Get $get) => in_array($get("students.{$index}.type"), ['rescheduled', 'additional']) && $get("students.{$index}.session_status") !== 'absent'),

                                    Forms\Components\Textarea::make("students.{$index}.comment1")
                                        ->label('Tutor Comment')
                                        ->placeholder('Enter your comments for this session...'),

                                    Forms\Components\Hidden::make("students.{$index}.student_id")->default($student->id),
                                    Forms\Components\Hidden::make("students.{$index}.tutor_id")->default($user->id),
                                ]);
                        })->toArray();

                        return [
                            Forms\Components\Wizard::make($steps)->skippable(),
                        ];
                    })
                    ->action(function (array $data) {
                        // Check if the 'students' key exists before proceeding to prevent errors
                        if (!array_key_exists('students', $data)) {
                            return;
                        }
                        
                        $studentsData = $data['students'];
                        foreach ($studentsData as $sessionData) {
                            $student = Student::find($sessionData['student_id']);
                            if ($student) {
                                // Provide default values for all possible missing fields.
                                $attendanceData = [
                                    'status' => 'pending',
                                    'comment1' => $sessionData['comment1'] ?? null,
                                    'tutor_id' => $sessionData['tutor_id'],
                                    'type' => $sessionData['session_status'] === 'absent' ? 'absent' : ($sessionData['type'] ?? 'on-schedule'),
                                    'subject' => $sessionData['subject'] ?? ($sessionData['session_status'] === 'absent' ? 'Absent' : null),
                                    'topic' => $sessionData['topic'] ?? ($sessionData['session_status'] === 'absent' ? 'Absent' : null),
                                    'duration' => $sessionData['duration'] ?? ($sessionData['session_status'] === 'absent' ? 0 : null),
                                    'scheduled_date' => $sessionData['scheduled_date'] ?? Carbon::today(),
                                    'actual_date' => $sessionData['actual_date'] ?? null,
                                    'reason' => $sessionData['reason'] ?? null,
                                ];

                                $student->attendances()->create($attendanceData);
                            }
                        }

                        Notification::make()
                            ->title('Attendance Filled')
                            ->body('All daily attendance records have been successfully saved.')
                            ->success()
                            ->send();
                    })
                    ->modalWidth('2xl')
                    ->modalSubmitActionLabel('Save Attendance')
                    ->modalCancelActionLabel('Cancel'),
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
