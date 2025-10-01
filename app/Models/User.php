<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // Crucial for Spatie/Filament Shield roles
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    // Removed HasApiTokens trait to fix the "Unknown class" error
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * Removed 'api_token' or similar if it was related to Sanctum
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ----------------------------------------------------------------------
    // NEW MESSAGE RELATIONSHIPS (REQUIRED FOR FILAMENT FILTERING)
    // ----------------------------------------------------------------------

    /**
     * Get the messages that the user has sent.
     */
    public function sentMessages(): HasMany
    {
        // Links to the 'sender_id' column in the messages table
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Get the messages that are specifically addressed to this user (individual messages).
     */
    public function receivedMessages(): HasMany
    {
        // Links to the 'recipient_user_id' column in the messages table
        return $this->hasMany(Message::class, 'recipient_user_id');
    }
}
