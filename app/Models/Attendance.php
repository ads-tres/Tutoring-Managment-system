<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'tutor_id',
        'type',
        'scheduled_date',
        'actual_date',
        'reason',
        'subject',
        'topic',
        'duration',
        'status',
        'payment_status',
        'comment1',
        'comment2',
        'approved_by_id',
        'session_status',
        'period_number',
        'date',
        
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'actual_date' => 'date',
        // 'payment_status' => 'boolean',
        'date' => 'date',
        'duration' => 'float',
        'period_number' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}
