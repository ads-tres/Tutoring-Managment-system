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
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
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

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('student_id')
                ->relationship('student', 'full_name')
                ->required()
                ->native(false),

            Forms\Components\Select::make('tutor_id')
                ->relationship('tutor', 'name')
                ->required()
                ->native(false),

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
                ->visible(fn(Get $get) => $get('type') === 'rescheduled')
                ->native(false),

            Forms\Components\Textarea::make('reason')
                ->label('Reschedule Reason')
                ->rows(2)
                ->maxLength(500)
                ->visible(fn(Get $get) => $get('type') === 'rescheduled'),

            Forms\Components\TextInput::make('subject')
                ->required()
                ->visible(fn(Get $get) => $get('type') !== 'absent'),
            Forms\Components\TextInput::make('topic')
                ->required()
                ->visible(fn(Get $get) => $get('type') !== 'absent'),

            Forms\Components\TextInput::make('duration')
                ->label('Duration (hours)')
                ->numeric()
                ->required()
                ->minValue(1)
                ->maxValue(8)
                ->visible(fn(Get $get) => $get('type') !== 'absent'),

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

            Forms\Components\Textarea::make('comment1')->label('Comment 1')->rows(2),
            Forms\Components\Textarea::make('comment2')->label('Comment 2')->rows(2),
        ]);
    }

    public static function table(Table $table): Table
    {
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
                Tables\Columns\TextColumn::make('topic')
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
                Tables\Columns\TextColumn::make('actual_date')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Reschedule Reason')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_by')
                    ->label('Updated By')
                    ->getStateUsing(fn(Attendance $record) => $record->approvedBy?->name)
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn() => Auth::user()->hasRole('manager')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('comment1')
                    ->label('Comment 1')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('comment2')
                    ->label('Comment 2')
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
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'on-schedule' => 'On-Schedule',
                        'additional' => 'Additional',
                        'rescheduled' => 'Rescheduled',
                        'absent' => 'Absent',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Parent Approve Action
                Tables\Actions\Action::make('approve')
                    ->visible(fn(Model $record) => Auth::user()->hasRole('parent') && in_array($record->status, ['pending', 'rejected']))
                    ->action(fn(Model $record) => $record->update(['status' => 'approved', 'approved_by_id' => Auth::user()->id]))
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check-circle'),
                // Parent Reject Action
                Tables\Actions\Action::make('reject')
                    ->visible(fn(Model $record) => Auth::user()->hasRole('parent') && $record->status === 'pending')
                    ->action(fn(Model $record) => $record->update(['status' => 'rejected', 'approved_by_id' => Auth::user()->id]))
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-x-circle'),
                // Manager Approve Action
                Tables\Actions\Action::make('approveAttendance')
                    ->label('Approve')
                    ->visible(fn(Model $record) => Auth::user()->hasRole('manager') && in_array($record->status, ['pending', 'rejected']))
                    ->action(fn($record) => $record->update(['status' => 'approved', 'approved_by_id' => Auth::user()->id]))
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->tooltip('Approve this attendance record'),
                // Tutor "Ask for Reschedule" Action
                Tables\Actions\Action::make('ask_for_reschedule')
                    ->label('Ask for Reschedule')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn(Model $record) => Auth::user()->hasRole('tutor') && $record->type === 'absent' && $record->status === 'pending')
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
                    ->tooltip('Request a new date for this absent session'),
                // Manager "Approve Reschedule" Action
                Tables\Actions\Action::make('approve_reschedule')
                    ->label('Approve Reschedule')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Model $record) => Auth::user()->hasRole('manager') && $record->status === 'reschedule_requested')
                    ->action(fn(Attendance $record) => $record->update([
                        'status' => 'approved',
                        'type' => 'rescheduled',
                    ]))
                    ->requiresConfirmation()
                    ->tooltip('Approve and update this attendance record for the rescheduled session'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => !Auth::user()->hasRole('parent')),
                    // Parent Bulk Approve Action
                    Tables\Actions\BulkAction::make('approveSelected')
                        ->label('Approve Selected')
                        ->action(fn($records) => $records->each->update(['status' => 'approved', 'approved_by_id' => Auth::user()->id]))
                        ->requiresConfirmation()
                        ->color('success')
                        ->icon('heroicon-o-check')
                        ->visible(fn() => Auth::user()->hasRole('parent')),
                    // Parent Bulk Reject Action
                    Tables\Actions\BulkAction::make('rejectSelected')
                        ->label('Reject Selected')
                        ->action(fn($records) => $records->each->update(['status' => 'rejected', 'approved_by_id' => Auth::user()->id]))
                        ->requiresConfirmation()
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->visible(fn() => Auth::user()->hasRole('parent')),
                    // Parent Bulk Approve with Note Action
                    BulkAction::make('approveSelectedWithComment')
                        ->label('Approve with Note')
                        ->form([
                            Forms\Components\Textarea::make('note')
                                ->label('Optional Note')
                                ->rows(2),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each(function ($record) use ($data) {
                                $record->update([
                                    'status' => 'approved',
                                    'comment2' => $data['note'],
                                    'approved_by_id' => Auth::user()->id,
                                ]);
                            });
                        })
                        ->requiresConfirmation()
                        ->color('success')
                        ->icon('heroicon-o-chat-bubble-left')
                        ->visible(fn() => Auth::user()->hasRole('parent')),
                    // Manager Bulk Approve Action
                    BulkAction::make('approveSelectedForManager')
                        ->label('Approve Selected')
                        ->action(function ($records) {
                            $records->each(fn($record) => $record->update(['status' => 'approved', 'approved_by_id' => Auth::user()->id]));
                        })
                        ->requiresConfirmation()
                        ->color('success')
                        ->icon('heroicon-o-check')
                        ->visible(fn() => Auth::user()->hasRole('manager')),
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
                // New action for managers to add a new attendance record
                // Tables\Actions\Action::make('add_new_attendance')
                //     ->label('Add New Attendance')
                //     ->icon('heroicon-o-plus')
                //     ->color('primary')
                //     ->visible(fn() => Auth::user()->hasRole('manager'))
                //     ->form(fn(Form $form) => self::form($form))
                //     ->action(function (array $data) {
                //          // Check if the type is 'absent' and provide default values if needed
                //         if ($data['type'] === 'absent') {
                //             $data['subject'] = 'N/A';
                //             $data['topic'] = 'N/A';
                //             $data['duration'] = 0;
                //         }
                //         Attendance::create($data);
                //         Notification::make()
                //             ->title('Attendance record created')
                //             ->body('A new attendance record has been successfully created.')
                //             ->success()
                //             ->send();
                //     })
                //     ->modalWidth('xl')
                //     ->modalSubmitActionLabel('Create Record'),
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
