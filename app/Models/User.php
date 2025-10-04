<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // Crucial for Spatie/Filament Shield roles
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Panel;

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
        // 'first_name',
        'middle_name',
        'last_name',
        'phone',
        'telegram_chat_id',
        'dob',
        'profile_photo_path',
        'is_suspended',
        'region_scope_type',
        'region_scope_id',
        'monthly_target_hours',
        'salary_per_hour',
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
        'dob' => 'date',
        'is_suspended' => 'boolean',
        'monthly_target_hours' => 'integer',
        'salary_per_hour' => 'decimal:2',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        // Allow access if user has 'manager' role (or whatever admin role you use)
        return $this->hasRole('manager')|| $this->hasRole('parent') || $this->hasRole('tutor');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'parent_id', 'id');
    }

     // Attendance through students
     public function attendancesViaChildren()
     {
         return $this->hasManyThrough(
             Attendance::class,   // final model
             Student::class,      // through
             'parent_id',          // foreign key on students table
             'student_id',         // foreign key on attendances table
             'id',                // local key on users
             'id'                 // local key on students
         );
        }

    

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
