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
        // Check if the 'payments' table already exists before attempting to create it
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                
                // Foreign key to the Student model
                $table->foreignId('student_id')->constrained()->onDelete('cascade');
                
                // The total amount received from the payer
                $table->decimal('amount', 10, 2)->comment('Total amount received in this transaction.'); 
                
                // The amount used to settle debt (should be <= amount)
                $table->decimal('amount_applied', 10, 2)->comment('Amount applied directly to debt.');
                
                // The remaining amount that became credit (amount - amount_applied)
                $table->decimal('amount_credit', 10, 2)->comment('Amount converted to credit/balance.');
                
                // The student's balance after this transaction
                $table->decimal('balance_after', 10, 2)->comment('Student balance/credit after payment.');
                
                // An array of session IDs covered by this payment
                // Using 'json' to store the array of covered session IDs
                $table->json('covered_sessions')->nullable(); 
                
                // Optional note about the payment
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
        Schema::dropIfExists('payments');
    }
};
