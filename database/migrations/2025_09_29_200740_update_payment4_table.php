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
        // Only run this if the payments table exists
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                // Add the missing column: amount_applied
                if (!Schema::hasColumn('payments', 'amount_applied')) {
                    $table->decimal('amount_applied', 10, 2)->after('amount')
                          ->comment('Amount applied directly to settle debt.');
                }

                // The logic also requires amount_credit, which often goes missing.
                if (!Schema::hasColumn('payments', 'amount_credit')) {
                    $table->decimal('amount_credit', 10, 2)->after('amount_applied')
                          ->comment('Amount converted to credit/balance.');
                }
                
                // Add the missing column: balance_after
                if (!Schema::hasColumn('payments', 'balance_after')) {
                    $table->decimal('balance_after', 10, 2)->after('amount_credit')
                          ->comment('Student balance/credit after payment.');
                }
                
                // Add the missing column: covered_sessions (JSON array)
                if (!Schema::hasColumn('payments', 'covered_sessions')) {
                    $table->json('covered_sessions')->nullable()->after('balance_after')
                          ->comment('Array of session IDs covered by this payment.'); 
                }
                
                // Ensure 'note' is also present in case it was missed in the initial setup.
                if (!Schema::hasColumn('payments', 'note')) {
                    $table->text('note')->nullable()->after('covered_sessions');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Remove the columns if rolling back
            $table->dropColumn([
                'amount_applied', 
                'amount_credit', 
                'balance_after', 
                'covered_sessions'
            ]);
            // If 'note' was explicitly added here, you'd drop it too, but often it's in the original migration.
        });
    }
};
