<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Apply a payment to the student's debt, starting with the oldest unpaid, approved sessions.
     *
     * @param Student $student The student receiving the payment.
     * @param float $amount The total amount received from the payer.
     * @param string|null $note Optional note for the payment.
     * @return Payment|null The newly created Payment record, or null on failure.
     */
    public function applyPayment(Student $student, float $amount, ?string $note = null): ?Payment
    {
        return DB::transaction(function () use ($student, $amount, $note) {
            
            // 1. Initial State and Payment Pool
            $balanceBefore = (float) $student->balance;
            // The payment pool is the new amount + existing credit
            $paymentPool = $amount + $balanceBefore; 
            $sessionPrice = (float) $student->price_per_session;

            if ($sessionPrice <= 0) {
                // Handle case where pricing is not set, treat as full credit.
                $student->update(['balance' => $paymentPool]);
                
                return Payment::create([
                    'student_id'        => $student->id,
                    'amount'            => $amount,
                    'amount_applied'    => 0.00,
                    'amount_credit'     => $amount,
                    'balance_after'     => $paymentPool,
                    'covered_sessions'  => [],
                    'note'              => 'Price per session is zero. Amount added to credit. ' . $note,
                ]);
            }

            // 2. Fetch billable debt sessions (approved, unpaid, not absent)
            $attendances = $student->attendances()
                ->where('status', 'approved')
                ->where('payment_status', 'unpaid')
                // ->where('session_status', '!=', 'absent')
                ->orderBy('scheduled_date', 'asc')
                ->get();

            $coveredSessionIds = [];
            $totalAppliedToSessions = 0.00;
            
            // 3. Apply payment pool to cover sessions one by one
            foreach ($attendances as $attendance) {
                $sessionCost = $sessionPrice; 

                if ($paymentPool >= $sessionCost) {
                    // Fully cover this session and MARK AS PAID
                    $attendance->update(['payment_status' => 'paid']);
                    
                    $paymentPool -= $sessionCost;
                    $totalAppliedToSessions += $sessionCost;
                    $coveredSessionIds[] = $attendance->id;
                } else {
                    // Not enough money left to cover this session, stop here.
                    break; 
                }
            }
            
            // 4. Calculate final debt/credit allocation for logging
            
            // Total amount applied to debt is how much cost was covered
            $totalDebtReduction = $totalAppliedToSessions; 

            // How much of the NEW PAYMENT went toward debt vs credit
            if ($totalDebtReduction > $balanceBefore) {
                // If debt reduction exceeds the old balance, the new payment was used for debt
                $newPaymentAppliedToDebt = $totalDebtReduction - $balanceBefore; 
                $amountConvertedToCredit = max(0, $amount - $newPaymentAppliedToDebt); 
            } else {
                // If old balance covered all debt reduction, the full new payment is credit
                $newPaymentAppliedToDebt = 0.00;
                $amountConvertedToCredit = $amount;
            }

            // 5. Update Student's Credit Balance (final remaining payment pool)
            $student->update([
                'balance' => $paymentPool, 
            ]);

            // 6. Record payment log
            return Payment::create([
                'student_id'        => $student->id,
                'amount'            => $amount,
                'amount_applied'    => $newPaymentAppliedToDebt,
                'amount_credit'     => $amountConvertedToCredit,
                'balance_after'     => $paymentPool,
                'covered_sessions'  => $coveredSessionIds,
                'note'              => $note,
            ]);
        });
    }
}
