<?php

namespace App\Filament\Resources;

use App\Models\Attendance;
use App\Models\Student;
use App\Services\PaymentService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Resources\AccountantResource\Pages;

class AccountantResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Payments Overview';
    protected static ?string $slug = 'payments-overview';
    protected static ?string $navigationGroup = 'Finance & Payments';

    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountants::route('/'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')->label('Student Name')->searchable(),

                // Should now correctly count sessions where payment_status is false (unpaid)
                TextColumn::make('unpaid_sessions_count')
                    ->label('Unpaid Sessions'),

                // rawDebtBeforeCredit should now show the total cost of those unpaid sessions
                TextColumn::make('raw_debt_before_credit')
                    ->label('Total Raw Debt')
                    ->money('ETB', 0)
                    ->tooltip('The total cost of all approved, unpaid sessions.'),

                TextColumn::make('balance')
                    ->label('Credit Balance')
                    ->money('ETB', 0)
                    ->color(fn (float $state): string => $state > 0 ? 'success' : 'gray')
                    ->description('Pre-paid amount (credit).'),
                    
                TextColumn::make('total_due')
                    ->label('Total Amount Due (Net)')
                    ->money('ETB', 0)
                    ->color(fn (float $state): string => $state > 0 ? 'danger' : 'success')
                    ->description('Debt outstanding after applying credit.'),
                    
                TextColumn::make('period_total')
                    ->label('Period Total (Full)')
                    ->money('ETB', 0), 
            ])
            ->actions([
                // ACTION: Record a payment
                Action::make('makePayment')
                    ->label('Record Payment')
                    ->icon('heroicon-o-credit-card')
                    ->button()
                    ->modalHeading(fn (Student $record) => "Record Payment for {$record->full_name}")
                    ->form([
                        TextInput::make('amount')
                            ->label('Payment Amount (ETB)')
                            ->numeric()
                            ->required()
                            ->default(0.00)
                            ->minValue(0.01)
                            // ->mask('9,999,999.99')
                            ->placeholder('e.g., 5000.00'),
                        
                        TextInput::make('note')
                            ->label('Payment Note (Optional)')
                            ->nullable()
                            ->maxLength(255),
                    ])
                    ->action(function (Student $record, array $data, PaymentService $paymentService) {
                        $paymentService->applyPayment($record, (float)$data['amount']);
                        
                        return \Filament\Notifications\Notification::make()
                            ->title('Payment Recorded')
                            ->body("ETB " . number_format($data['amount'], 2) . " applied to {$record->full_name}'s account.")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
