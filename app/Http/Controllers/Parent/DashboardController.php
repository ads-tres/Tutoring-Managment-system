<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;

class DashboardController extends Controller
{
    /**
     * Show parent's children attendance dashboard.
     */
    public function __invoke(Request $request)
    {
        $parent = Auth::user();

        // Sanity: check relation students exists
        $studentIds = $parent->students()->pluck('id');

        $attendances = Attendance::with('student')
            ->whereIn('student_id', $studentIds)
            ->orderByDesc('scheduled_date')
            ->paginate(10);

        return view('parent.dashboard', compact('attendances'));
    }
}
