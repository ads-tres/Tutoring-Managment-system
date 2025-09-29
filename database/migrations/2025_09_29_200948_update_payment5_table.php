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
        // Check if the payments table and necessary columns exist before modifying
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                // Set default for amount_credit to 0.00 if it exists
                if (Schema::hasColumn('payments', 'amount_credit')) {
                    $table->decimal('amount_credit', 10, 2)->default(0.00)->change();
                }
                
                // Safety check: ensure all other key fields also default to 0.00
                if (Schema::hasColumn('payments', 'amount_applied')) {
                    $table->decimal('amount_applied', 10, 2)->default(0.00)->change();
                }
                if (Schema::hasColumn('payments', 'balance_after')) {
                    $table->decimal('balance_after', 10, 2)->default(0.00)->change();
                }
                
                // Ensure covered_sessions can be null if not provided
                if (Schema::hasColumn('payments', 'covered_sessions')) {
                    $table->json('covered_sessions')->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations (optional, but good practice).
     */
    public function down(): void
    {
        // Revert the changes (remove default values if needed, or simply leave empty)
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                // Note: Reverting a 'change()' is complex, often best left undone unless critical.
                // For demonstration, we'll just show dropping the default.
                if (Schema::hasColumn('payments', 'amount_credit')) {
                    // This attempts to remove the default, making it NOT NULL again (if it was before)
                    // Depending on Laravel version, you might need to manually ensure it becomes NOT NULL if desired.
                    $table->decimal('amount_credit', 10, 2)->default(null)->change();
                }
            });
        }
    }
};
