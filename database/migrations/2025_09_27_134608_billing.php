<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Update Students Table with Period and Rate Data
        Schema::table('students', function (Blueprint $table) {
            // Drop old columns if they exist before creating the new ones
            if (Schema::hasColumn('students', 'period_sessions')) {
                $table->renameColumn('period_sessions', 'sessions_per_period');
            } else if (!Schema::hasColumn('students', 'sessions_per_period')) {
                $table->integer('sessions_per_period')->default(12)->after('full_name');
            }

            if (Schema::hasColumn('students', 'session_rate')) {
                $table->renameColumn('session_rate', 'price_per_period');
            } else if (!Schema::hasColumn('students', 'price_per_period')) {
                $table->decimal('price_per_period', 8, 2)->default(100)->after('sessions_per_period');
            }
            
            if (!Schema::hasColumn('students', 'balance')) {
                $table->decimal('balance', 10, 2)->default(0)->after('price_per_period');
            }
            if (!Schema::hasColumn('students', 'period_start')) {
                $table->date('period_start')->nullable()->after('balance');
            }
            if (!Schema::hasColumn('students', 'period_closed')) {
                $table->boolean('period_closed')->default(false)->after('period_start');
            }
        });
        
        // 2. Update Attendances Table with Billing Metrics
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'duration')) {
                $table->decimal('duration', 4, 2)->default(0)->after('date');
            }
            
            if (Schema::hasColumn('attendances', 'paid')) {
                $table->renameColumn('paid', 'payment_status');
            } else if (!Schema::hasColumn('attendances', 'payment_status')) {
                $table->boolean('payment_status')->default(false)->after('duration');
            }
            
            if (!Schema::hasColumn('attendances', 'period_number')) {
                $table->integer('period_number')->default(1)->after('payment_status');
            }
        });

        // 3. Payments Table (Ensure covered_sessions field is correct)
        if (Schema::hasTable('payments')) {
             Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'covered_sessions')) {
                    $table->json('covered_sessions')->comment('JSON array of attendance IDs covered by this payment')->nullable();
                }
             });
        }
    }

    public function down(): void
    {
        // Revert names on rollback
        Schema::table('students', function (Blueprint $table) {
            $table->renameColumn('sessions_per_period', 'period_sessions');
            $table->renameColumn('price_per_period', 'session_rate');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->renameColumn('payment_status', 'paid');
        });
    }
};
