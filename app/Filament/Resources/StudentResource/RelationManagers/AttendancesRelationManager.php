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
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->native(false),
                Forms\Components\DatePicker::make('scheduled_date')
                    ->required()
                    ->native(false),
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
                    ->options(['pending' => 'Pending', 'approved' => 'Approved'])
                    ->default('pending')
                    ->required()
                    ->native(false),
                Forms\Components\Select::make('payment_status')
                    ->options(['unpaid' => 'Unpaid', 'paid' => 'Paid'])
                    ->default('unpaid')
                    ->required()
                    ->native(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->columns([
                Tables\Columns\TextColumn::make('tutor.name')
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
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
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
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approveAttendance')
                    ->label('Approve')
                    ->visible(fn($record) => Auth::user()->hasRole('parent') && $record->status === 'pending')
                    ->action(fn($record) => $record->update(['status' => 'approved', 'approved_by_id' => Auth::user()->id]))
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->tooltip('Approve this attendance record'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('approveSelected')
                        ->label('Approve Selected')
                        ->action(function ($records) {
                            $records->each(fn($record) => $record->update(['status' => 'approved', 'approved_by_id' => Auth::user()->id]));
                        })
                        ->requiresConfirmation()
                        ->color('success')
                        ->icon('heroicon-o-check')
                        ->visible(fn() => Auth::user()->hasRole('parent')),
                ]),
            ])
            ->defaultSort('scheduled_date', 'desc');
    }
}
