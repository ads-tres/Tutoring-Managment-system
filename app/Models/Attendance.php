<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $student_id
 * @property int $tutor_id
 * @property string $type
 * @property \Illuminate\Support\Carbon $scheduled_date
 * @property \Illuminate\Support\Carbon|null $actual_date
 * @property string|null $reason
 * @property string $subject
 * @property string $topic
 * @property int $duration
 * @property string|null $comment1
 * @property string|null $comment2
 * @property string $status
 * @property string $payment_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Student $student
 * @property-read \App\Models\User $tutor
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereActualDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereComment1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereComment2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereScheduledDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereStudentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereTopic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereTutorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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

