<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Student;
use App\Notifications\ApproachingMaxSessions;
use Illuminate\Support\Facades\Notification;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // scheduled task to check student sessions and send alerts.
        $schedule->call(function () {
            $students = Student::where('status', 'active')->get();

            foreach ($students as $student) {
                // Get the maximum monthly sessions for the student.
                $max = $student->maxMonthlySessions();

                // Get approved sessions for the current month.
                $count = $student->attendances()
                    ->whereMonth('scheduled_date', now()->month)
                    ->where('status', 'approved')
                    ->count();

                // Check if the student is approaching the max limit (within 2 sessions).
                if ($count >= $max - 2 && $count < $max) {
                    // Trigger a notification to the parent.
                    Notification::send($student->parent, new ApproachingMaxSessions($student, $count, $max));

                    // Check if the parent has a region scope and a subordinate, and notify them.
                    if ($student->parent->region_scope_type && $student->parent->subordinate) {
                        Notification::send($student->parent->subordinate, new ApproachingMaxSessions($student, $count, $max));
                    }
                }
            }
        })->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
