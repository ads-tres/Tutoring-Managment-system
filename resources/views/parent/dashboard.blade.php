<x-app-layout>
    <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
    {{ __('My Children’s Attendance') }}
    </h2>
    </x-slot>
    
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if (session('status'))
                        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
                            {{ session('status') }}
                        </div>
                    @endif
    
                    <table class="w-full table-auto mb-6 border">
                        <thead>
                            <tr class="bg-black-100">
                                <th class="px-3 py-2">Student</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendances as $att)
                                <tr class="border-t">
                                    <td class="px-3 py-2">{{ $att->student->full_name }}</td>
                                    <td>{{ $att->scheduled_date->format('Y-m-d') }}</td>
                                    <td>{{ ucfirst(str_replace('-', ' ', $att->type)) }}</td>
                                    <td>{{ ucfirst($att->status) }}</td>
                                    <td class="px-3 py-2 space-x-2">
                                        @if($att->status === 'pending')
                                            <form method="POST" action="{{ route('parent.attendance.approve', $att) }}" class="inline">
                                                @csrf @method('PUT')
                                                <button class="text-green-600">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('parent.attendance.dispute', $att) }}" class="inline">
                                                @csrf @method('PUT')
                                                <input name="comment" placeholder="Reason..." class="border rounded px-1" required>
                                                <button class="text-red-600">Dispute</button>
                                            </form>
                                        @else
                                            <span>—</span>
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
    
                    {{ $attendances->links() }}
                    
                    <a href="{{ route('parent.attendance.export') }}" class="bg-blue-600 text-white px-4 py-2 rounded mt-6 inline-block">
                        Download CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    </x-app-layout>