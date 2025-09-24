<x-filament::page>
 <div class="px-4 sm:px-6 lg:px-8">
 <div class="sm:flex sm:items-center">
 <div class="sm:flex-auto">

</div>
 </div>

 <div class="mt-8 flow-root">
 <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
<table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700 rounded-lg shadow-lg">
<thead>
<tr>
@foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day)
<th scope="col" class="py-3.5 px-3 text-left text-sm font-semibold text-gray-900 dark:text-white sticky top-0 bg-gray-50 dark:bg-gray-700">
{{ $day }}
</th>
@endforeach
</tr>
</thead>
<tbody class="divide-y divide-gray-200 bg-white dark:bg-gray-800 dark:divide-gray-700">
<tr>
@php
 // Fetch the schedule data once
$weeklySchedule = $this->getSchedule();
@endphp

@foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day)
<td class="px-3 py-4 text-sm text-gray-500 dark:text-gray-400 align-top">
 @if (isset($weeklySchedule[$day]) && count($weeklySchedule[$day]) > 0)
<ul role="list" class="space-y-2">
@foreach ($weeklySchedule[$day] as $session)
<li class="p-3 bg-primary-50 dark:bg-gray-700/50 rounded-lg border border-primary-100 dark:border-gray-600 shadow-sm">
<div class="font-bold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($session['time'])->format('g:i A') }}</div>
<div class="text-xs text-gray-200 dark:text-gray-300">Student: {{ $session['student_name'] }}</div>
 </li>
@endforeach
</ul>
@else
<p class="text-center text-gray-400 dark:text-gray-500 text-sm italic">No Sessions</p>
 @endif
 </td>
 @endforeach
 </tr>
 </tbody>
 </table>
 </div>
 </div>
 </div>
 </div>
</x-filament::page>