<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'content',
        'recipient_target',
    ];
    
    /**
     * Get the sender (Manager) of the message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Scope to filter messages intended for a specific user.
     * This is the core logic for the Message Inbox.
     */
    public function scopeForUser($query, User $user)
    {
        // 1. Target is 'ALL_USERS'
        $query->where('recipient_target', 'ALL_USERS')
            
            // 2. Target is the specific user ID (e.g., 'user:5')
            ->orWhere('recipient_target', 'user:' . $user->id);

        // 3. Target is the user's role(s) (e.g., 'role:parent')
        foreach ($user->getRoleNames() as $role) {
            $query->orWhere('recipient_target', 'role:' . $role);
        }

        return $query;
    }
}
