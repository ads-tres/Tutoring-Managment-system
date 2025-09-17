<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Auth;

class AttendancePolicy
{
    /**
     * Determine whether the user can view a specific attendance record.
     * This allows a parent to view attendance records for their own child.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        if ($user->hasRole(['manager', 'tutor'])) {
            return true;
        }

        if ($user->hasRole('parent')) {
            // Check if the parent owns the student associated with this attendance record.
            return $user->id === $attendance->student->parent_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create attendance records.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['manager', 'tutor']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Attendance $attendance): bool
    {
        return $user->hasRole(['manager', 'tutor']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->hasRole(['manager']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Attendance $attendance): bool
    {
        return $user->hasRole(['manager']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Attendance $attendance): bool
    {
        return $user->hasRole(['manager']);
    }
}
