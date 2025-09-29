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
     */
    public function applyPayment(Student $student, float $amount): void
    {
        DB::transaction(function () use ($student, $amount) {
            $balanceBefore = (float) $student->balance;
            $remaining = $amount;

            // Fetch approved, unpaid, non-absent sessions oldest first
            $attendances = Attendance::where('student_id', $student->id)
                ->where('status', 'approved')
                ->where('payment_status', 'unpaid') // <-- FIXED: Use 'unpaid' string
                ->where('session_status', '!=', 'absent')
                ->orderBy('scheduled_date', 'asc') // <-- FIXED: Use the correct column name
                ->get();

            $coveredSessions = [];
            $amountAppliedToDebt = 0.00; 

            foreach ($attendances as $attendance) {
                // Cost calculation: Rate * Duration (Assuming price_per_period is the rate per period/session)
                // Assuming session cost is based on student's price_per_period property (price per session)
                $sessionCost = (float) $student->price_per_period * (float) $attendance->duration;

                if ($remaining >= $sessionCost) {
                    // Fully cover this session
                    $attendance->update(['payment_status' => 'paid']); // <-- FIXED: Use 'paid' string
                    $remaining -= $sessionCost;
                    $amountAppliedToDebt += $sessionCost;
                    $coveredSessions[] = $attendance->id;
                } else {
                    // Partial payment - stop here, the session remains unpaid
                    break;
                }
            }
            
            // 1. Update student balance: remaining amount becomes credit
            $student->balance = $balanceBefore + $remaining;
            $student->save();

            // 2. Record payment log
            Payment::create([
                'student_id'      => $student->id,
                'amount'          => $amount,
                'amount_applied'  => $amountAppliedToDebt,
                'amount_credit'   => $remaining,
                'balance_after'   => $student->balance,
                'covered_sessions' => $coveredSessions,
                'note'            => "Applied payment. Old credit balance: " . number_format($balanceBefore, 2)
            ]);

            // 3. Check for period closing (Optional, assuming this logic is correct)
            // $this->checkAndClosePeriod($student); 
        });
    }

    // /**
    //  * Checks if the required number of sessions for the current period have been paid.
    //  */
    // private function checkAndClosePeriod(Student $student): void
    // {
    //     // ... period closing logic, ensure it also uses 'paid'/'unpaid' strings and correct date columns
    // }
}
