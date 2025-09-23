<?php

namespace App\Filament\Pages;

use App\Models\Student;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class TutorSchedulePage extends Page
{
    protected static ?string $slug = 'schedule';

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Tutor Management';

    protected static string $view = 'filament.pages.tutor-schedule-page';

    public $students = [];

    public function mount(): void
    {
        $tutor = Auth::user();

        if ($tutor) {
            $this->students = Student::where('tutor_id', $tutor->id)->get();
        }
    }

    public function getTitle(): string
    {
        return 'My Schedule';
    }
}
