<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'comment1',
        'comment2',
        'status',
        'payment_status',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'actual_date' => 'date',
    ];

    public function student() { return $this->belongsTo(Student::class); }
    public function tutor() { return $this->belongsTo(User::class, 'tutor_id'); }
}

