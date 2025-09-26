<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    protected $casts = [
        'dob' => 'date',
        'initial_skills' => 'array',
        'start_date' => 'date',
        'scheduled_days' => 'array',
        'starting_time' => 'datetime:H:i',
    ];

    /**
     * Get the parent that owns the student.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id', 'id');
    }

    /**
     * Get the tutor that is assigned to the student.
     */
    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id', 'id');
    }

    /**
     * Get the attendance records for the student.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** Total amount for one full payment period */
    public function getPeriodTotalAttribute(): float
    {
        // Ensure properties are treated as numbers
        $sessions = (int) $this->sessions_per_period;
        $price = (float) $this->price_per_session;
        return $sessions * $price;
    }

    /** Count of unpaid sessions */
    public function getUnpaidSessionsCountAttribute(): int
    {
        // Ensure the paid column is cast correctly in the Attendance model
        return $this->attendances()->where('payment_status', false)->count();
    }

    /** Total due for unpaid sessions */
    public function getTotalDueAttribute(): float
    {
        return $this->unpaid_sessions_count * (float) $this->price_per_session;
    }

    /** Total completed sessions */
    public function getTotalCompletedSessionsAttribute(): int
    {
        return $this->attendances()->count();
    }
}
