<div>
    <x-filament::page>
        <div class="space-y-4">
            <h2 class="text-xl font-bold">My Weekly Schedule</h2>

            @php
                $schedule = $this->getSchedule();
            @endphp

            @if (empty($schedule))
                <div class="text-center text-gray-500">
                    No sessions scheduled for this week.
                </div>
            @else
                <table class="table-auto w-full border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-300 px-4 py-2">Date</th>
                            <th class="border border-gray-300 px-4 py-2">Day</th>
                            <th class="border border-gray-300 px-4 py-2">Time</th>
                            <th class="border border-gray-300 px-4 py-2">Student Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($schedule as $session)
                            <tr>
                                <td class="border border-gray-300 px-4 py-2">{{ $session['date'] }}</td>
                                <td class="border border-gray-300 px-4 py-2">{{ $session['day'] }}</td>
                                <td class="border border-gray-300 px-4 py-2">{{ $session['time'] }}</td>
                                <td class="border border-gray-300 px-4 py-2">{{ $session['student_name'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </x-filament::page>
</div>
