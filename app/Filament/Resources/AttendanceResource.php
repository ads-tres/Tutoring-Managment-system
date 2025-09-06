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
                ->options(['pending' => 'Pending', 'approved' => 'Approved'])
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
                Tables\Columns\TextColumn::make('student.full_name')->label('Student'),
                Tables\Columns\TextColumn::make('tutor.name')->label('Tutor'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'on-schedule' => 'primary',
                        'additional' => 'warning',
                        'rescheduled' => 'info',
                        default => 'gray',
                    })
                    ->label('Type'),
                Tables\Columns\TextColumn::make('scheduled_date')->date(),
                Tables\Columns\TextColumn::make('actual_date')->date()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'unpaid' => 'danger',
                        'paid' => 'success',
                        default => 'gray',
                    })
                    ->label('Paid?'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('comment1')->label('Comment 1')->limit(30)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('comment2')->label('Comment 2')->limit(30)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'approved' => 'Approved']),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'on-schedule' => 'On-Schedule',
                        'additional' => 'Additional',
                        'rescheduled' => 'Rescheduled',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->action(fn($record) => $record->update(['status' => 'approved']))
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check-circle'),
            ])
            ->bulkActions([
                // The bulk actions are now grouped for a cleaner UI
                BulkActionGroup::make([
                    BulkAction::make('approveSelected')
                        ->label('Approve Selected')
                        ->action(function ($records) {
                            $records->each->update(['status' => 'approved']);
                        })
                        ->requiresConfirmation()
                        ->color('success')
                        ->icon('heroicon-o-check'),
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
                                    'comment2' => $data['note'], // admin comment field
                                ]);
                            });
                        })
                        ->requiresConfirmation()
                        ->color('success')
                        ->icon('heroicon-o-chat-bubble-left'),
                    DeleteBulkAction::make(),
                ]),
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
