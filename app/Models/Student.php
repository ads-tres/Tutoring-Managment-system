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
        'scheduled_days',
        'start_time',
        'session_length_minutes',
        'end_time',
        'session_duration',
        'status',
        'start_date',
        'student_image',
        'parents_image',
        'sessions_per_period', 
        'price_per_session', 
        'balance', 
        'map_location',
        
    ];

    protected $casts = [
        'dob' => 'date',
        'initial_skills' => 'array',
        'start_date' => 'date',
        'scheduled_days' => 'array',
        'starting_time' => 'datetime:H:i',
        'sessions_per_period' => 'integer', 
        'price_per_period' => 'float', 
        'balance' => 'float', 
        // 'duration' => 'integer',
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

    public function getPeriodTotalAttribute(): float
    {
        $sessions = (int) $this->sessions_per_period;
        $price = (float) $this->price_per_session;
        return $sessions * $price;
    }

    /**
     * Count of unpaid, billable sessions (Approved and not Absent).
     */
    public function getUnpaidSessionsCountAttribute(): int
    {
        return $this->attendances()
            ->where('status', 'approved')
            ->where('payment_status', 'unpaid')
            // ->where('session_status', '!=', 'absent')
            // ->count(); 
            ->sum('duration');
    }
    
    /** * Calculates the Raw Debt Before Credit (Total cost of all unpaid sessions).
     */
    public function getRawDebtBeforeCreditAttribute(): float
    {
        $unpaidCount = $this->unpaid_sessions_count; 
        return $unpaidCount * (float) $this->price_per_session;
    }

    /**
     * Calculates the total NET amount due (Debt minus Credit/Balance).
     * This value can be negative if the student has excess credit.
     */
    public function getTotalDueAttribute(): float
    {
        $rawDebt = $this->raw_debt_before_credit; 
        // Debt - Credit = Net Due. This is the correct calculation.
        return $rawDebt - (float) $this->balance; 
    }
}
