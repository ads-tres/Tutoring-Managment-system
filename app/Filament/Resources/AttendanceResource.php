<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages\ListAttendances;
use App\Filament\Resources\AttendanceResource\Pages\CreateAttendance;
use App\Filament\Resources\AttendanceResource\Pages\EditAttendance;
use App\Models\Attendance;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Fieldset;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Filament\Notifications\Notification;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Students Management';

    /**
     * Filters attendance records based on the logged-in user's role.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        // If the logged-in user is a parent, only show attendance for their children.
        if ($user->hasRole('parent')) {
            return Attendance::query()->whereHas('student', function ($q) use ($user) {
                $q->where('parent_id', $user->id);
            });
        }

        // If the logged-in user is a tutor, only show attendance for their assigned students.
        if ($user->hasRole('tutor')) {
            return Attendance::query()->where('tutor_id', $user->id);
        }

        // Managers and other roles see all attendance records.
        return Attendance::query();
    }
    
    /**
     * Global access control: Only managers can create new records.
     */
    public static function canCreate(): bool
    {
        return Auth::user()->hasRole('manager');
    }

    /**
     * Global access control: Only managers can edit records.
     */
    public static function canEdit(Model $record): bool
    {
        return Auth::user()->hasRole('manager');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Student and Tutor fields
            Forms\Components\Select::make('student_id')
                ->relationship('student', 'full_name')
                ->required()
                ->native(false)
                ->columnSpan(1),

            Forms\Components\Select::make('tutor_id')
                ->relationship('tutor', 'name')
                ->required()
                ->native(false)
                ->columnSpan(1),

            // Type and Date fields
            Forms\Components\Fieldset::make('Session Details')->columns(3)->schema([
                Forms\Components\Select::make('type')
                    ->options([
                        'on-schedule' => 'On-Schedule',
                        'additional' => 'Additional',
                        'rescheduled' => 'Rescheduled',
                        'absent' => 'Absent',
                    ])
                    ->required()
                    ->live()
                    ->native(false),

                Forms\Components\DatePicker::make('scheduled_date')
                    ->required()
                    ->native(false),

                Forms\Components\DatePicker::make('actual_date')
                    ->label('Actual Session Date')
                    ->visible(fn(Get $get) => in_array($get('type'), ['rescheduled', 'additional']))
                    ->required(fn(Get $get) => in_array($get('type'), ['rescheduled', 'additional']))
                    ->native(false),
            ]),


            // Content and Duration fields
            Forms\Components\Fieldset::make('Content and Duration')
                ->columns(3)
                ->visible(fn(Get $get) => $get('type') !== 'absent')
                ->schema([
                    Forms\Components\TextInput::make('subject')
                        ->required(),
                    Forms\Components\TextInput::make('topic')
                        ->required(),
                    Forms\Components\TextInput::make('duration')
                        ->label('Duration (hours)')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(8),
                ]),

            Forms\Components\Textarea::make('reason')
                ->label('Reschedule/Additional/Absence Reason')
                ->rows(2)
                ->maxLength(500)
                ->visible(fn(Get $get) => in_array($get('type'), ['rescheduled', 'additional', 'absent']))
                ->columnSpanFull(),

            // Status fields
            Forms\Components\Fieldset::make('Status')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            'reschedule_requested' => 'Reschedule Requested'
                        ])
                        ->default('pending')
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('payment_status')
                        ->options(['unpaid' => 'Unpaid', 'paid' => 'Paid'])
                        ->default('unpaid')
                        ->required()
                        ->native(false),
                ]),


            // Comment fields
            Forms\Components\Fieldset::make('Comments')
                ->columns(2)
                ->schema([
                    Forms\Components\Textarea::make('comment1')->label('Tutor Comment')->rows(2),
                    Forms\Components\Textarea::make('comment2')->label('Parent/Manager Comment')->rows(2),
                ]),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        $isParent = $user->hasRole('parent');
        $isTutor = $user->hasRole('tutor');
        $isManager = $user->hasRole('manager');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('Student')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('tutor.name')
                    ->label('Tutor')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject')
                    ->sortable()
                    ->searchable()
                    ->limit(20)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration (hrs)')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => ucwords(str_replace('-', ' ', $state)))
                    ->color(fn(string $state): string => match ($state) {
                        'on-schedule' => 'primary',
                        'additional' => 'warning',
                        'rescheduled' => 'info',
                        'absent' => 'danger',
                        default => 'gray',
                    })
                    ->label('Type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'reschedule_requested' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'unpaid' => 'danger',
                        'paid' => 'success',
                        default => 'gray',
                    })
                    ->label('Paid?')
                    ->sortable()
                    ->visible($isManager), // Only managers/admins typically track payment status
                Tables\Columns\TextColumn::make('updated_by')
                    ->label('Updated By')
                    ->getStateUsing(fn(Attendance $record) => $record->approvedBy?->name ?? 'N/A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible($isManager),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('comment1')
                    ->label('Tutor Comment')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('comment2')
                    ->label('Parent/Manager Comment')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'reschedule_requested' => 'Reschedule Requested'
                    ])
                    ->label('Approval Status'),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'on-schedule' => 'On-Schedule',
                        'additional' => 'Additional',
                        'rescheduled' => 'Rescheduled',
                        'absent' => 'Absent',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options(['unpaid' => 'Unpaid', 'paid' => 'Paid'])
                    ->visible($isManager),
                Tables\Filters\Filter::make('scheduled_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_date', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                // Edit action is now controlled by the static canEdit() method, limiting it to managers.
                Tables\Actions\EditAction::make(),

                // Parent Approve Action (Visible to Parents, status is pending/rejected)
                Tables\Actions\Action::make('parent_approve')
                    ->label('Approve')
                    ->visible(fn(Model $record) => $isParent && in_array($record->status, ['pending', 'rejected']))
                    ->action(fn(Model $record) => $record->update(['status' => 'approved', 'approved_by_id' => $user->id]))
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->tooltip('Approve this session report.'),

                // Parent Reject Action (Visible to Parents, status is pending)
                Tables\Actions\Action::make('parent_reject')
                    ->label('Reject')
                    ->visible(fn(Model $record) => $isParent && $record->status === 'pending')
                    ->action(fn(Model $record) => $record->update(['status' => 'rejected', 'approved_by_id' => $user->id]))
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->tooltip('Reject this session report.'),

                // Tutor "Ask for Reschedule" Action (Visible to Tutors for pending absent sessions)
                Tables\Actions\Action::make('tutor_ask_for_reschedule')
                    ->label('Ask for Reschedule')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn(Model $record) => $isTutor && $record->type === 'absent' && $record->status === 'pending')
                    ->form([
                        Forms\Components\DatePicker::make('new_date')
                            ->label('New Reschedule Date')
                            ->native(false)
                            ->required(),
                        Forms\Components\Textarea::make('reschedule_reason')
                            ->label('Reason for Reschedule')
                            ->rows(2)
                            ->maxLength(500)
                            ->required(),
                    ])
                    ->action(fn(Attendance $record, array $data) => $record->update([
                        'status' => 'reschedule_requested',
                        'actual_date' => $data['new_date'],
                        'reason' => $data['reschedule_reason'],
                    ]))
                    ->requiresConfirmation()
                    ->tooltip('Request a new date for this absent session.'),

                // Manager Approve Action (Visible to Managers)
                Tables\Actions\Action::make('manager_approve_attendance')
                    ->label('Approve')
                    ->visible(fn(Model $record) => $isManager && in_array($record->status, ['pending', 'rejected', 'reschedule_requested']))
                    ->action(function ($record) use ($user) {
                        $updateData = ['status' => 'approved', 'approved_by_id' => $user->id];
                        // If approving a reschedule request, also update the type to 'rescheduled'
                        if ($record->status === 'reschedule_requested') {
                            $updateData['type'] = 'rescheduled';
                        }
                        $record->update($updateData);
                    })
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->tooltip('Approve this attendance record.'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    // Bulk Delete is now visible only to managers.
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => $isManager),

                    // Bulk Approve (Parent & Manager)
                    BulkAction::make('bulkApprove')
                        ->label('Bulk Approve Selected')
                        ->action(fn($records) => $records->each(fn($record) => $record->update(['status' => 'approved', 'approved_by_id' => $user->id])))
                        ->requiresConfirmation()
                        ->color('success')
                        ->icon('heroicon-o-check')
                        ->visible(fn() => $isParent || $isManager),

                    // Parent Bulk Reject Action
                    BulkAction::make('parentBulkReject')
                        ->label('Reject Selected')
                        ->action(fn($records) => $records->each(fn($record) => $record->update(['status' => 'rejected', 'approved_by_id' => $user->id])))
                        ->requiresConfirmation()
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->visible(fn() => $isParent),

                    // Parent Bulk Approve with Note Action
                    BulkAction::make('approveSelectedWithComment')
                        ->label('Approve with Note')
                        ->form([
                            Forms\Components\Textarea::make('note')
                                ->label('Optional Note (Comment 2)')
                                ->rows(2),
                        ])
                        ->action(function ($records, array $data) use ($user) {
                            $records->each(function ($record) use ($data, $user) {
                                $record->update([
                                    'status' => 'approved',
                                    'comment2' => $data['note'],
                                    'approved_by_id' => $user->id,
                                ]);
                            });
                        })
                        ->requiresConfirmation()
                        ->color('success')
                        ->icon('heroicon-o-chat-bubble-left')
                        ->visible(fn() => $isParent),
                ]),
            ])
            ->headerActions([
                // Standard Create Action (Visibility remains, but is globally restricted by canCreate())
                CreateAction::make()
                    ->visible($isManager)
                    ->label('Add New Record'),

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

    public static function getPages(): array
    {
        return [
            'index' => ListAttendances::route('/'),
            'create' => CreateAttendance::route('/create'),
            'edit' => EditAttendance::route('/{record}/edit'),
        ];
    }
}
