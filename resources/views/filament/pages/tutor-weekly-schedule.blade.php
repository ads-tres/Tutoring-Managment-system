<x-filament::page>
    <x-filament::card class="p-0">
        <div class="px-4 py-4 sm:px-6 lg:px-8">
            
            {{-- Schedule Table Container --}}
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            {{-- Day Headers --}}
                            @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day)
                                <th scope="col" class="py-3 px-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200 sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                                    {{ $day }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr>
                            @php
                                // Fetch the schedule data once
                                $weeklySchedule = $this->getSchedule();
                            @endphp

                            
                            @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day)
                                <td class="px-3 py-4 text-sm align-top w-1/5">
                                    @if (isset($weeklySchedule[$day]) && count($weeklySchedule[$day]) > 0)
                                        
                                        
                                        <ul role="list" class="space-y-3">
                                            @foreach ($weeklySchedule[$day] as $session)
                                                <li class="p-3 rounded-xl border border-primary-300 dark:border-primary-600 bg-primary-50 dark:bg-gray-700 shadow-sm transition hover:shadow-md">
                                                    
                                                    
                                                    <div class="font-bold text-base text-primary-600 dark:text-primary-400">
                                                        {{ \Carbon\Carbon::parse($session['time'])->format('g:i A') }}
                                                    </div>
                                                    
                                                    
                                                    <div class="text-sm text-gray-200 dark:text-gray-300 truncate">
                                                        <span class="font-medium"></span> {{ $session['student_name'] }}
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        
                                        <div class="p-3 text-center rounded-lg bg-gray-50 dark:bg-gray-800 border border-dashed border-gray-200 dark:border-gray-700">
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                                No Sessions
                                            </p>
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </x-filament::card>
</x-filament::page>