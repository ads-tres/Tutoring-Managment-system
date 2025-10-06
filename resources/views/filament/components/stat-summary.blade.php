<div class="p-6 bg-white  shadow-lg mb-6 dark:bg-gray-800">
    <h2 class="text-xl font-extrabold text-gray-700 dark:text-gray-200 mb-4">
        Global Financial Overview
    </h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Total Unpaid Sessions -->
        <div class="p-4 bg-gray-50 rounded-lg dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Unpaid Sessions</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                {{ number_format($stats['totalUnpaidSessions']) }}
            </p>
        </div>

        <!-- Total Raw Debt -->
        <div class="p-4 bg-gray-50 rounded-lg dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Raw Debt (Gross)</p>
            <p class="text-2xl font-bold text-red-500 mt-1">
                ETB {{ number_format($stats['totalRawDebt'], 2) }}
            </p>
        </div>
        
        <!-- Total Credit Balance -->
        <div class="p-4 bg-gray-50 rounded-lg dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Credit Balance</p>
            <p class="text-2xl font-bold text-green-500 mt-1">
                ETB {{ number_format($stats['totalCreditBalance'], 2) }}
            </p>
        </div>

        <!-- Total Net Due -->
        <div class="p-4 bg-gray-50 rounded-lg dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $netDueLabel }}</p>
            <p class="text-2xl font-bold {{ $netDueColor }} mt-1">
                ETB {{ $netDueValue }}
            </p>
        </div>
    </div>
</div>
