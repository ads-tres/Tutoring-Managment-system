<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;

class AttendanceController extends Controller
{
    /**
     * Parent approves a pending attendance record.
     */
    public function approve(Attendance $attendance)
    {
        $parent = Auth::user();

        // Security: ensure attendance belongs to one of parent's children
        if (! $parent->students()->pluck('id')->contains($attendance->student_id)) {
            abort(403, 'Unauthorized attendance.');
        }

        // Only update if status is pending
        if ($attendance->status === 'pending') {
            $attendance->update(['status' => 'approved']);
        }

        return back()->with('status', 'Attendance approved.');
    }

    /**
     * Parent disputes / adds comment to a pending attendance record.
     */
    public function dispute(Request $request, Attendance $attendance)
    {
        $parent = Auth::user();

        if (! $parent->students()->pluck('id')->contains($attendance->student_id)) {
            abort(403, 'Unauthorized attendance.');
        }

        $request->validate([
            'comment' => 'required|string|max:500',
        ]);

        if ($attendance->status === 'pending') {
            $attendance->update([
                'status'    => 'pending', // remains pending, but record dispute
                'comment2'  => $request->comment, // second comment field for parent
            ]);
        }

        return back()->with('status', 'Attendance disputed.');
    }

    /**
     * Export attendance data for all children to CSV.
     */
    public function export(Request $request)
    {
        $parent = Auth::user();
        $studentIds = $parent->students()->pluck('id');

        $records = Attendance::with('student')
            ->whereIn('student_id', $studentIds)
            ->orderBy('scheduled_date', 'asc')
            ->get();

        // CSV header
        $csvHeader = ['Student', 'Date', 'Type', 'Status', 'Subject', 'Topic'];

        // Prepare content
        $filename = 'attendance_export_' . now()->format('Y_m_d_H_i_s') . '.csv';

        $callback = function () use ($csvHeader, $records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $csvHeader);

            foreach ($records as $a) {
                fputcsv($handle, [
                    $a->student->full_name,
                    $a->scheduled_date->format('Y-m-d'),
                    ucfirst($a->type),
                    ucfirst($a->status),
                    $a->subject,
                    $a->topic,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }
}
