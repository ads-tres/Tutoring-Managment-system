<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Update Students Table with Period and Rate Data
        Schema::table('students', function (Blueprint $table) {
            // These columns should be created if they don't exist
            if (!Schema::hasColumn('students', 'sessions_per_period')) {
                $table->integer('sessions_per_period')->default(12)->after('full_name');
            }
            if (!Schema::hasColumn('students', 'price_per_period')) {
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
            if (!Schema::hasColumn('attendances', 'payment_status')) {
                $table->boolean('payment_status')->default(false)->after('duration'); 
            }
            if (!Schema::hasColumn('attendances', 'period_number')) {
                $table->integer('period_number')->default(1)->after('payment_status');
            }
        });

        // 3. Create Payments Table for Audit Log
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->json('covered_sessions')->comment('JSON array of attendance IDs covered by this payment');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['sessions_per_period', 'price_per_period', 'balance', 'period_start', 'period_closed']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['duration', 'payment_status', 'period_number']);
        });
        
        Schema::dropIfExists('payments');
    }
};
