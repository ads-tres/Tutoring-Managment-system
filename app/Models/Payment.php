<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'student_id',
        'amount',
        'amount_applied',
        'amount_credit',
        'balance_after',
        'covered_sessions',
        'note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'float',
        'amount_applied' => 'float',
        'amount_credit' => 'float',
        'balance_after' => 'float',
        'covered_sessions' => 'array',
    ];

    /**
     * Get the student associated with the payment.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
