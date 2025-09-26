<?php

namespace App\Filament\Resources;

use App\Models\Attendance;
use App\Models\Student;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Resources\AccountantResource\Pages;

class AccountantResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Payments Overview';
    protected static ?string $slug = 'payments-overview';

    // Disable default CRUD pages as this is an overview resource
    public static function getPages(): array
    {
        // Assuming you have this page created in app/Filament/Resources/AccountantResource/Pages/ListAccountants.php
        return [
            'index' => Pages\ListAccountants::route('/'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')->label('Student Name')->searchable(),

                // Using getStateUsing() for calculated properties for stability (removes the login error)
                TextColumn::make('period_total')
                    ->label('Period Total (Full)')
                    ->getStateUsing(fn (Student $record) => $record->period_total)
                    ->money('ETB'),

                TextColumn::make('unpaid_sessions_count')
                    ->label('Unpaid Sessions')
                    ->getStateUsing(fn (Student $record) => $record->unpaid_sessions_count),

                TextColumn::make('total_due')
                    ->label('Total Due')
                    ->getStateUsing(fn (Student $record) => $record->total_due)
                    ->money('ETB'),
            ])
            ->bulkActions([
                BulkAction::make('mark_paid')
                    ->label('Mark All Unpaid Sessions as Paid')
                    ->action(function (Collection $records) {
                        // *** CRITICAL FIX *** Update payment_status to 'paid' where payment_status is 'unpaid'
                        Attendance::whereIn('student_id', $records->pluck('id'))
                            ->where('payment_status', 'unpaid')
                            ->update(['payment_status' => 'paid']);
                    })
                    ->requiresConfirmation(),
            ]);
    }
}
