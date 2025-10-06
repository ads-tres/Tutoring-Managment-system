<?php

namespace App\Filament\Resources;

use App\Models\Student;
use App\Models\Attendance;
use App\Services\PaymentService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use App\Filament\Resources\AccountantResource\Pages;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\NumericInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class AccountantResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Payments Overview';
    protected static ?string $slug = 'payments-overview';
    protected static ?string $navigationGroup = 'Finance & Payments';

    public static function canViewAny(): bool
    {
        return Auth::user() && Auth::user()->hasAnyRole(['accountant', 'manager']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountants::route('/'),
        ];
    }

    /**
     * Calculate global statistics by aggregating across all students.
     */
    protected static function getGlobalStats(): array
    {
        // 1. Get raw data needed for calculation (requires fetching data due to accessors)
        $students = Student::get(['id', 'balance', 'price_per_session']);

        // 2. Aggregate unpaid attendance sessions (requires one DB query)
        $totalUnpaidSessions = Attendance::where('status', 'approved')
            ->where('payment_status', 'unpaid')
            // ->where('session_status', '!=', 'absent')
            ->sum('duration');

        // 3. Initialize sums
        $totalRawDebt = 0.00;
        $totalCreditBalance = 0.00;

        // 4. Calculate Raw Debt and Total Credit via iteration
        foreach ($students as $student) {
            // Note: We can't use $student->unpaid_sessions_count directly here 
            // without complex queries, so we rely on the total count from step 2 for the header,
            // and use the individual calculation for debt based on price_per_session.

            // Since we can't efficiently calculate individual unpaid sessions without loading all attendances, 
            // we will use the total raw debt based on the existing balance logic for consistency.
            // A more complex query involving JOIN and SUM on the Attendances table would be better,
            // but for simplicity and to stay within constraints, we will calculate based on a simplified model 
            // if full attendance data cannot be loaded efficiently here.

            // For the global stats, we'll calculate based on the Student model's total debt:
            $unpaidCount = $student->unpaid_sessions_count; // This still works, but runs a query per student (less performant)
            $totalRawDebt += $unpaidCount * (float) $student->price_per_session;
            $totalCreditBalance += (float) $student->balance;
        }

        // Calculate final net debt
        $totalNetDue = $totalRawDebt - $totalCreditBalance;

        return [
            'totalUnpaidSessions' => $totalUnpaidSessions,
            'totalRawDebt' => $totalRawDebt,
            'totalCreditBalance' => $totalCreditBalance,
            'totalNetDue' => $totalNetDue,
        ];
    }

    public static function table(Table $table): Table
    {
        $stats = static::getGlobalStats();

        return $table
            ->header(static::renderStatsHeader($stats))
            ->columns([
                TextColumn::make('Roll No.')->label('Roll No.')->rowIndex(),
                TextColumn::make('full_name')->label('Student Name')->searchable(),
                TextColumn::make('price_per_session')
                    ->label('Price Per Session')
                    ->money('ETB'),

                TextColumn::make('sessions_per_period')
                    ->label('Session Per Period'),
                TextColumn::make('unpaid_sessions_count')
                    ->label('Unpaid Sessions'),

                TextColumn::make('raw_debt_before_credit')
                    ->label('Total Raw Debt')
                    ->money('ETB', 0)
                    ->tooltip('The total cost of all approved, unpaid sessions.'),

                TextColumn::make('balance')
                    ->label('Credit Balance')
                    ->money('ETB', 0)
                    ->color(fn(float $state): string => $state > 0 ? 'success' : 'gray')
                    ->description('Pre-paid amount (credit).'),

                TextColumn::make('total_due')
                    ->label('Total Amount Due (Net)')
                    ->money('ETB', 0)
                    // Display absolute value
                    ->getStateUsing(fn(Student $record): float => abs($record->total_due))
                    // Color based on the signed value
                    ->color(fn(Student $record): string => $record->total_due > 0 ? 'danger' : 'success')
                    ->description('Net financial position'),

                TextColumn::make('period_total')
                    ->label('Period Total (Full)')
                    ->money('ETB', 0),
                    TextColumn::make('parent.phone')
                    ->label('Parent Phone')
                    ->searchable()
                    ->sortable()
                    ->tooltip('Phone number of the associated parent user record.'),
            ])
            ->filters([
                // 1. Simple Filter for a database column
                SelectFilter::make('sessions_per_period')
                    ->label('Sessions per Period')
                    ->options(Student::distinct()->pluck('sessions_per_period', 'sessions_per_period'))
                    ->default(null),
                
                // 2. Custom Ternary Filter for Full Period Debt
                TernaryFilter::make('full_period_debt')
                    ->label('Full Period Debt Status')
                    ->placeholder('Show All')
                    ->default(null)
                    ->falseLabel('Less than Full Period Debt')
                    ->trueLabel('Has Full Period Debt')
                    ->queries(
                        true: function (Builder $query) {
                            // Filter where Unpaid Sessions Count == sessions_per_period
                            return $query->whereRaw('
                                (SELECT COALESCE(SUM(duration), 0) FROM attendances 
                                 WHERE student_id = students.id 
                                 AND status = "approved" 
                                 AND payment_status = "unpaid") >= students.sessions_per_period
                            ');
                        },
                        false: function (Builder $query) {
                            // Filter where Unpaid Sessions Count < sessions_per_period
                            return $query->whereRaw('
                                (SELECT COALESCE(SUM(duration), 0) FROM attendances 
                                 WHERE student_id = students.id 
                                 AND status = "approved" 
                                 AND payment_status = "unpaid") < students.sessions_per_period
                            ');
                        },
                        blank: null,
                    ),
            ])
            ->actions([
                Action::make('makePayment')
                    ->label('Record Payment')
                    ->icon('heroicon-o-credit-card')
                    ->button()
                    ->modalHeading(fn(Student $record) => "Record Payment for {$record->full_name}")
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
                            (float) $data['amount'],
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

    /**
     * Renders the global statistics overview panel.
     */
    protected static function renderStatsHeader(array $stats): \Illuminate\View\View
    {
        $netDueColor = $stats['totalNetDue'] > 0 ? 'text-red-600' : 'text-green-600';
        $netDueLabel = $stats['totalNetDue'] > 0 ? 'Total Net Debt' : 'Total Net Credit';
        $netDueValue = number_format(abs($stats['totalNetDue']), 2);

        return view('filament.components.stat-summary', compact('stats', 'netDueColor', 'netDueLabel', 'netDueValue'));
    }
}
