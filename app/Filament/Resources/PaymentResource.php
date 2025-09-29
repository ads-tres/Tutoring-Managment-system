<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar'; 
    protected static ?string $navigationLabel = 'Payment History';

    // Disable creation/editing since payments should be recorded via the Student action
    protected static bool $canCreate = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'full_name')
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('amount')
                    ->label('Total Amount Received')
                    ->numeric()
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('amount_applied')
                    ->label('Amount Applied to Debt')
                    ->numeric()
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('balance_after')
                    ->label('Balance After Payment')
                    ->numeric()
                    ->required()
                    ->disabled(),
                Forms\Components\Textarea::make('covered_sessions')
                    ->label('Sessions Covered (IDs)')
                    ->disabled()
                    ->helperText('List of Attendance IDs covered by this payment. (JSON Array)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.full_name')->label('Student')->searchable(),
                TextColumn::make('created_at')->dateTime()->label('Payment Date')->sortable(),
                TextColumn::make('amount')->money('ETB', 0)->label('Amount Paid'),
                
                // New Column: Shows how much was actually applied to clear sessions
                TextColumn::make('amount_applied')->money('ETB', 0)->label('Debt Covered'),

                TextColumn::make('covered_sessions')
                    ->label('Sessions Covered')
                    ->getStateUsing(fn (Payment $record) => count($record->covered_sessions) . ' session(s)')
                    ->tooltip(fn (Payment $record) => 'IDs: ' . implode(', ', $record->covered_sessions)),

                TextColumn::make('balance_after')
                    ->label('New Balance')
                    ->money('ETB', 0)
                    ->description(fn (float $state): string => $state > 0 ? 'Credit' : 'Debt')
                    ->color(fn (float $state): string => $state >= 0 ? 'success' : 'danger'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
        ];
    }
}
