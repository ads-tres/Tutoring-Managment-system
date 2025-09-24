<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Student;

class TutorWeeklySchedule extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Schedule';
    protected static ?string $title = 'My Weekly Schedule';

    public function mount()
    {
        // Ensure only tutors can access this page
        if (!Auth::user()->hasRole('tutor')) {
            abort(403);
        }
    }

    public static function canView(): bool
    {
        return Auth::user()->hasRole('tutor');
    }

    public function getSchedule(): array
    {
        $tutorId = Auth::id();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $students = Student::where('tutor_id', $tutorId)->get();

        $schedule = [];
        foreach ($students as $student) {
            foreach ($student->scheduled_days as $day) {
                $date = Carbon::parse($startOfWeek)->modify($day);
                if ($date->between($startOfWeek, $endOfWeek)) {
                    $schedule[] = [
                        'date' => $date->toDateString(),
                        'day' => ucfirst($day),
                        'time' => $student->start_time,
                        'student_name' => $student->full_name,
                    ];
                    
                }
                
            }
            // dd($schedule);
        }

        return collect($schedule)->sortBy(['date', 'time'])->values()->toArray();
    }

    protected static string $view = 'filament.pages.tutor-weekly-schedule';
}



