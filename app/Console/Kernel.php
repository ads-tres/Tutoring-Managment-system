<?php
use App\Models\Student;
use App\Notifications\ApproachingMaxSessions;
use Illuminate\Support\Facades\Notification;

protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        $students = Student::where('status', 'active')->get();

        foreach ($students as $student) {
            $max = $student->maxMonthlySessions(); // defined in Student model
            $count = $student->attendances()
                ->whereMonth('scheduled_date', now()->month)
                ->where('status', 'approved')
                ->count();

            if ($count >= $max - 2 && $count < $max) {
                // Notify the parent
                $student->parent->notify(new ApproachingMaxSessions($student, $count, $max));

                // Optionally, notify assigned subordinate if applicable
                // $student->subordinate->notify(...);
            }
        }
    })->daily();
}
