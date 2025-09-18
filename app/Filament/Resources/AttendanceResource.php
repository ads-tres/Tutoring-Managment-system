<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages\ListAttendances;
use App\Filament\Resources\AttendanceResource\Pages\CreateAttendance;
use App\Filament\Resources\AttendanceResource\Pages\EditAttendance;
use App\Models\Attendance;
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

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

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

            Forms\Components\TextInput::make('subject')->required(),
            Forms\Components\TextInput::make('topic')->required(),

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
                    ->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'on-schedule' => 'On-Schedule',
                        'additional' => 'Additional',
                        'rescheduled' => 'Rescheduled',
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
                    ->label('Approve Attendance')
                    ->visible(fn(Model $record) => Auth::user()->hasRole('manager') && in_array($record->status, ['pending', 'rejected']))
                    ->action(fn($record) => $record->update(['status' => 'approved', 'approved_by_id' => Auth::user()->id]))
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->tooltip('Approve this attendance record'),
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
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();

                if ($user->hasRole('parent')) {
                    $query->whereHas('student', function ($q) use ($user) {
                        $q->where('parent_id', $user->id);
                    });
                }
            });
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
