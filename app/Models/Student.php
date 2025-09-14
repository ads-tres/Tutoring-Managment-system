<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
Use App\Models\User;

/**
 * @property int $id
 * @property int $parent_id
 * @property string $full_name
 * @property string|null $student_phone
 * @property string $sex
 * @property \Illuminate\Support\Carbon|null $dob
 * @property array<array-key, mixed>|null $initial_skills
 * @property string|null $father_name
 * @property string|null $father_phone
 * @property string|null $mother_name
 * @property string|null $mother_phone
 * @property string|null $region
 * @property string|null $city
 * @property string|null $subcity
 * @property string|null $district
 * @property string|null $kebele
 * @property string|null $house_number
 * @property string|null $street
 * @property string|null $landmark
 * @property string|null $school_name
 * @property string|null $school_type
 * @property string|null $grade
 * @property string|null $frequency
 * @property string|null $start_time
 * @property int|null $session_length_minutes
 * @property string|null $end_time
 * @property int|null $session_duration
 * @property string $status
 * @property array<array-key, mixed>|null $scheduled_days
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property string|null $student_image
 * @property string|null $parents_image
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attendance> $attendances
 * @property-read int|null $attendances_count
 * @property-read \App\Models\User $parent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereDistrict($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereDob($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereFatherName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereFatherPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereGrade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereHouseNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereInitialSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereKebele($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereLandmark($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereMotherName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereMotherPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereParentsImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereRegion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereScheduledDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereSchoolName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereSchoolType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereSessionDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereSessionLengthMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereSex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereStreet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereStudentImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereStudentPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereSubcity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Student whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
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
        'scheduled_days', 'start_time', 'session_length_minutes',
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

    // public function parent()
    // {
    //     return $this->belongsTo(User::class, 'parent_id');
    // }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function maxMonthlySessions(): int {
        $daysPerWeek = count($this->scheduled_days ?? []);
        return $daysPerWeek * 4;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id', 'id');
        
    }
}

