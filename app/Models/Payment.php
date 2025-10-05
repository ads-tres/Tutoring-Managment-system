<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'amount',
        'amount_applied', // field to track debt coverage
        'balance_after',
        'covered_sessions',
        'note',
    ];

    protected $casts = [
        'covered_sessions' => 'array',
        'amount' => 'float',
        'amount_applied' => 'float',
        'balance_after' => 'float',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
