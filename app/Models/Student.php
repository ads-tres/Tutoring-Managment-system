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

    public function maxMonthlySessions(): int
    {
        $daysPerWeek = count($this->scheduled_days ?? []);
        return $daysPerWeek * 4;
    }
}
