<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'tutor_id',
        'full_name',
        'student_phone',
        'sex',
        'dob',
        'initial_skills',
        'father_name',
        'father_phone',
        'mother_name',
        'mother_phone',
        'region',
        'city',
        'subcity',
        'district',
        'kebele',
        'house_number',
        'street',
        'landmark',
        'school_name',
        'school_type',
        'grade',
        'frequency',
        'scheduled_date',
        'start_time',
        'session_length_minutes',
        'end_time',
        'session_duration',
        'status',
        'start_date',
        'student_image',
        'parents_image',
        'sessions_per_period', 
        'price_per_period', 
        'balance', 
    ];

    protected $casts = [
        'dob' => 'date',
        'initial_skills' => 'array',
        'start_date' => 'date',
        'scheduled_date' => 'array',
        'starting_time' => 'datetime:H:i',
        'sessions_per_period' => 'integer', 
        'price_per_period' => 'float', 
        'balance' => 'float', 
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id', 'id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id', 'id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
    
    // --- Accessors for Billing ---

    /**
     * Total amount for one full payment period (Period Total column).
     */
    public function getPeriodTotalAttribute(): float
    {
        $sessions = (int) $this->sessions_per_period;
        $price = (float) $this->price_per_period; 
        return $sessions * $price;
    }

    /**
     * Count of unpaid, billable sessions (Unpaid Sessions column).
     */
    public function getUnpaidSessionsCountAttribute(): int
    {
        return $this->attendances()
            ->where('status', 'approved')
            ->where('payment_status', 'unpaid')
            ->where('session_status', '!=', 'absent') 
            ->count();
    }
    
    /**
     * Calculates the Raw Debt Before Credit (Total Raw Debt column).
     */
    public function getRawDebtBeforeCreditAttribute(): float
    {
        $unpaidCount = $this->unpaid_sessions_count; 
        return $unpaidCount * (float) $this->price_per_period; 
    }

    /**
     * Calculates the total NET amount due (Total Amount Due (Net) column).
     */
    public function getTotalDueAttribute(): float
    {
        $rawDebt = $this->raw_debt_before_credit; 
        $netDue = $rawDebt - (float) $this->balance;
        return max(0, $netDue);
    }

    public function getTotalCompletedSessionsAttribute(): int
    {
        return $this->attendances()->count();
    }

    /**
     * Function to mark a full period's worth of sessions as paid, starting with the oldest.
     */
    public function markthesessionsinsideoneperiod(){
        // 1. Get the IDs of the oldest unpaid, approved, and non-absent sessions
        $sessionIds = $this->attendances()
            ->where('payment_status', 'unpaid')
            ->where('status', 'approved')
            ->where('session_status', '!=', 'absent') 
            ->orderBy('scheduled_date', 'asc')        
            ->limit($this->sessions_per_period)
            ->pluck('id');

        // 2. Update the records found in the collection to 'paid'
        return $this->attendances()
            ->whereIn('id', $sessionIds)
            ->update(['payment_status' => 'paid']); 
    }
}
