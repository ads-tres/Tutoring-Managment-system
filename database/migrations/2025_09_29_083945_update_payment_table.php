<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // --- A. Update Students Table ---
        Schema::table('students', function (Blueprint $table) {
            // Check for column existence to prevent errors
            if (!Schema::hasColumn('students', 'balance')) {
                // Balance field: POSITIVE for CREDIT, NEGATIVE for DEBT/AMOUNT DUE.
                $table->decimal('balance', 10, 2)->default(0.00)->after('price_per_period');
            }
            if (!Schema::hasColumn('students', 'period_closed')) {
                $table->boolean('period_closed')->default(false)->after('balance');
            }
            if (!Schema::hasColumn('students', 'period_start')) {
                $table->date('period_start')->nullable()->after('period_closed');
            }
            if (!Schema::hasColumn('students', 'sessions_per_period')) {
                $table->integer('sessions_per_period')->default(10)->after('period_start');
            }
        });

        // --- B. Update Attendances Table ---
        Schema::table('attendances', function (Blueprint $table) {
            // Check for column existence to prevent errors
            if (!Schema::hasColumn('attendances', 'payment_status')) {
                // Status must be a STRING: 'paid' or 'unpaid' (as requested)
                $table->string('payment_status')->default('unpaid')->after('status');
            }
            if (!Schema::hasColumn('attendances', 'period_number')) {
                $table->integer('period_number')->default(1)->after('payment_status');
            }
        });

        // --- C. Create Payments Table ---
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->cascadeOnDelete();
                $table->decimal('amount', 10, 2); // The total amount received
                $table->decimal('amount_applied', 10, 2)->default(0.00); // Amount used to clear debt
                $table->decimal('balance_after', 10, 2); // Student's balance after transaction
                $table->json('covered_sessions'); // Array of attendance IDs covered
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert actions in reverse, checking for table/column existence
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'sessions_per_period')) { $table->dropColumn('sessions_per_period'); }
            if (Schema::hasColumn('students', 'period_start')) { $table->dropColumn('period_start'); }
            if (Schema::hasColumn('students', 'period_closed')) { $table->dropColumn('period_closed'); }
            if (Schema::hasColumn('students', 'balance')) { $table->dropColumn('balance'); }
        });

        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'period_number')) { $table->dropColumn('period_number'); }
            if (Schema::hasColumn('attendances', 'payment_status')) { $table->dropColumn('payment_status'); }
        });

        if (Schema::hasTable('payments')) {
            Schema::dropIfExists('payments');
        }
    }
};
