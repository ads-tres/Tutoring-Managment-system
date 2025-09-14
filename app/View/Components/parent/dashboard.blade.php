{{-- parent/dashboard.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6">My Children’s Attendance</h1>

    @if(session('status'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
            {{ session('status') }}
        </div>
    @endif

    <table class="min-w-full bg-white border">
        <thead>
            <tr class="bg-gray-200">
                <th class="px-4 py-2">Student</th>
                <th class="px-4 py-2">Date</th>
                <th class="px-4 py-2">Type</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $att)
                <tr class="border-t">
                    <td class="px-4 py-2">{{ $att->student->full_name }}</td>
                    <td class="px-4 py-2">{{ $att->scheduled_date->format('Y-m-d') }}</td>
                    <td class="px-4 py-2">{{ ucfirst(str_replace('-', ' ', $att->type)) }}</td>
                    <td class="px-4 py-2">{{ ucfirst($att->status) }}</td>
                    <td class="px-4 py-2 space-x-2">
                        @if($att->status === 'pending')
                            <form method="POST" action="{{ route('parent.attendance.approve', $att) }}" class="inline">
                                @csrf
                                @method('PUT')
                                <button class="text-green-600 underline">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('parent.attendance.dispute', $att) }}" class="inline">
                                @csrf
                                @method('PUT')
                                <input type="text" name="comment" placeholder="Reason..." class="border rounded px-1" required>
                                <button class="text-red-600 underline">Dispute</button>
                            </form>
                        @else
                            <span class="text-gray-500">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-2 text-center">No attendance records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $attendances->links() }}
    </div>

    <div class="mt-6">
        <a href="{{ route('parent.attendance.export') }}" class="bg-blue-600 text-white px-4 py-2 rounded">Download CSV</a>
    </div>
</div>
@endsection
