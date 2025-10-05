<?php

namespace App\Filament\Resources;

use App\Models\Student;
use App\Services\PaymentService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use App\Filament\Resources\AccountantResource\Pages;
use Illuminate\Support\Facades\Auth;

class AccountantResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Payments Overview';
    protected static ?string $slug = 'payments-overview';
    protected static ?string $navigationGroup = 'Finance & Payments';

    public static function canViewAny(): bool
    {
        // We assume the User model has the Spatie\Permission HasRoles trait for hasAnyRole()
        return Auth::user() && Auth::user()->hasAnyRole(['accountant', 'manager']);
    }

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
                TextColumn::make( 'full_name')->label('Student Name')->searchable(),

                TextColumn::make('unpaid_sessions_count')
                    ->label('Unpaid Sessions'),

                TextColumn::make('raw_debt_before_credit')
                    ->label('Total Raw Debt')
                    ->money('ETB', 0)
                    ->tooltip('The total cost of all approved, unpaid sessions.'),

                TextColumn::make('balance')
                    ->label('Credit Balance')
                    ->money('ETB', 0)
                    ->color(fn (float $state): string => $state > 0 ? 'success' : 'gray')
                    ->description('Pre-paid amount (credit).'),
                    
                // Use absolute value for display, but the accessor calculates debt-credit
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
                            ->minValue(0.00)
                            ->placeholder('e.g., 5000.00'),
                        
                        TextInput::make('note')
                            ->label('Payment Note (Optional)')
                            ->nullable()
                            ->maxLength(255),
                    ])
                    ->action(function (Student $record, array $data, PaymentService $paymentService) {
                        $payment = $paymentService->applyPayment(
                            $record, 
                            (float)$data['amount'],
                            $data['note'] ?? null
                        );

                        if (!$payment) {
                             return \Filament\Notifications\Notification::make()
                                ->title('Payment Failed')
                                ->body('Could not process payment. Check if session price is set.')
                                ->danger()
                                ->send();
                        }
                        
                        $appliedCount = count($payment->covered_sessions);
                        $applied = number_format($payment->amount_applied, 2);
                        $credit = number_format($payment->amount_credit, 2);
                        
                        $body = "Covered **{$appliedCount}** sessions (ETB {$applied} applied to debt). Added ETB {$credit} to credit. New balance: ETB " . number_format($payment->balance_after, 2) . ".";

                        return \Filament\Notifications\Notification::make()
                            ->title('Payment Recorded')
                            ->body($body)
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
