<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendancesRelationManager extends RelationManager
{
    /**
     * The name of the Eloquent relationship.
     *
     * @var string
     */
    protected static string $relationship = 'attendances';

    /**
     * Define the form schema for creating and editing records.
     *
     * @param Form $form
     * @return Form
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tutor_id')
                    ->relationship('tutor', 'name')
                    ->required()
                    ->native(false)
                    ->default(fn() => Auth::user()->id)
                    ->visible(fn() => Auth::user()->hasRole('manager')),
                Forms\Components\Select::make('type')
                    ->options([
                        'on-schedule' => 'On-Schedule',
                        'additional' => 'Additional',
                        'rescheduled' => 'Rescheduled',
                    ])
                    ->required()
                    ->live()
                    ->native(false),
                Forms\Components\DatePicker::make('scheduled_date')
                    ->required()
                    ->native(false)
                    ->default(fn(Forms\Get $get) => $get('type') === 'on-schedule' ? now() : null)
                    ->disabled(fn(Forms\Get $get) => $get('type') === 'on-schedule' && Auth::user()->hasRole('tutor')),
                Forms\Components\DatePicker::make('actual_date')
                    ->visible(fn(Forms\Get $get) => $get('type') === 'rescheduled')
                    ->native(false),
                Forms\Components\Textarea::make('reason')
                    ->label('Reschedule Reason')
                    ->rows(2)
                    ->maxLength(500)
                    ->visible(fn(Forms\Get $get) => $get('type') === 'rescheduled'),
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
                Forms\Components\Select::make('status')
                    ->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'])
                    ->default('pending')
                    ->required()
                    ->native(false)
                    ->visible(fn() => !Auth::user()->hasRole('tutor')),
                Forms\Components\Select::make('payment_status')
                    ->options(['unpaid' => 'Unpaid', 'paid' => 'Paid'])
                    ->default('unpaid')
                    ->required()
                    ->native(false)
                    ->visible(fn() => !Auth::user()->hasRole('tutor')),
                Forms\Components\Textarea::make('comment1')
                    ->label('Tutor Comment')
                    ->rows(2)
                    ->visible(fn() => Auth::user()->hasRole('tutor') || Auth::user()->hasRole('manager')),
                Forms\Components\Textarea::make('comment2')
                    ->label('Parent Comment')
                    ->rows(2)
                    ->visible(fn() => Auth::user()->hasRole('parent') || Auth::user()->hasRole('manager'))
                    ->disabled(fn() => !Auth::user()->hasRole('parent')),
            ]);
    }

    /**
     * Define the table columns and actions.
     *
     * @param Table $table
     * @return Table
     */
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->modifyQueryUsing(function (Builder $query) {
                // Tutors can only see attendance for students assigned to them.
                if (Auth::user()->hasRole('tutor')) {
                    $query->where('tutor_id', Auth::user()->id);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('tutor.name')
                    ->label('Tutor')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject')
                    ->sortable()
                    ->searchable(),
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
                        default => 'gray',
                    })
                    ->sortable()
                    ->visible(fn() => !Auth::user()->hasRole('tutor')),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'unpaid' => 'danger',
                        'paid' => 'success',
                        default => 'gray',
                    })
                    ->label('Paid?')
                    ->sortable()
                    ->visible(fn() => Auth::user()->hasRole('manager')),
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
                    ->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'on-schedule' => 'On-Schedule',
                        'additional' => 'Additional',
                        'rescheduled' => 'Rescheduled',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Fill Today\'s Attendance')
                    ->visible(function () {
                        $user = Auth::user();
                        if (!$user->hasRole('tutor')) {
                            return false;
                        }
                        // Check if attendance already exists for today
                        return !$this->getOwnerRecord()->attendances()->whereDate('scheduled_date', Carbon::today())->exists();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Parent Approve Action
                Tables\Actions\Action::make('approve')
                    ->visible(fn($record) => Auth::user()->hasRole('parent') && in_array($record->status, ['pending', 'rejected']))
                    ->action(fn($record) => $record->update(['status' => 'approved', 'approved_by_id' => Auth::user()->id]))
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check-circle'),
                // Parent Reject Action
                Tables\Actions\Action::make('reject')
                    ->visible(fn($record) => Auth::user()->hasRole('parent') && $record->status === 'pending')
                    ->action(fn($record) => $record->update(['status' => 'rejected', 'approved_by_id' => Auth::user()->id]))
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-x-circle'),
                // Manager Approve Action
                Tables\Actions\Action::make('approveAttendance')
                    ->label('Approve Attendance')
                    ->visible(fn($record) => Auth::user()->hasRole('manager') && in_array($record->status, ['pending', 'rejected']))
                    ->action(fn($record) => $record->update(['status' => 'approved', 'approved_by_id' => Auth::user()->id]))
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->tooltip('Approve this attendance record'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            ->modifyQueryUsing(fn(Builder $query) => $query->withoutGlobalScopes([
                //
            ]));
    }
}
