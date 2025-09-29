<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\Student;
use App\Services\PaymentService; // Ensure this import is correct
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('full_name')->required(),
                Forms\Components\TextInput::make('email')->email(),
                
                // --- Billing Configuration ---
                Forms\Components\Fieldset::make('Billing Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('sessions_per_period') 
                            ->label('Sessions per Period')
                            ->integer()
                            ->default(12)
                            ->required(),
                        Forms\Components\TextInput::make('price_per_period')
                            ->label('Hourly Rate (ETB)')
                            ->numeric()
                            ->required(),
                        Forms\Components\DatePicker::make('period_start')
                            ->label('Current Period Start Date')
                            ->default(now()),
                        Forms\Components\Placeholder::make('period_status')
                            ->content(fn (Student $record) => $record->period_closed ? 'Closed' : 'Active')
                            ->label('Current Period Status'),
                    ]),
                // --- Financials (Read-only) ---
                Forms\Components\Fieldset::make('Current Financials')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('balance')
                            ->label('Credit/Debt Balance (ETB)')
                            ->readOnly(),
                        Forms\Components\Placeholder::make('unpaid_due')
                            ->content(fn (Student $record) => 'ETB ' . number_format($record->unpaid_due, 2))
                            ->label('Net Amount Due'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')->searchable()->sortable(),
                TextColumn::make('price_per_period')->label('Rate/Hr')->money('ETB', 0),
                TextColumn::make('sessions_per_period')->label('Sessions/Period')->alignCenter(),

                TextColumn::make('current_period_unpaid_sessions_count')
                    ->label('Unpaid Current Period')
                    ->badge()
                    ->alignEnd(),
                    
                TextColumn::make('unpaid_due')
                    ->label('Net Due')
                    ->money('ETB', 0)
                    ->color(fn (float $state): string => $state > 0 ? 'danger' : 'secondary')
                    ->alignEnd(),
                
                TextColumn::make('balance')
                    ->label('Credit/Debt')
                    ->money('ETB', 0)
                    ->color(fn (float $state): string => match (true) {
                        $state > 0 => 'success', 
                        $state < 0 => 'warning', 
                        default => 'secondary',
                    })
                    ->alignEnd(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // --- CRITICAL: THE MISSING PAYMENT ACTION ---
                Tables\Actions\Action::make('record_payment')
                    ->label('Record Payment')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Payment Amount (ETB)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->mask('999,999.99'),
                    ])
                    ->action(function (Student $record, array $data) {
                        // Resolve the service and apply payment logic
                        $service = app(PaymentService::class);
                        $service->applyPayment($record, (float)$data['amount']);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Payment Recorded')
                            ->body('Payment successfully applied, and student balance updated.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
