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
        Schema::table('messages', function (Blueprint $table) {
            // Add the new 'subject' column as a string (required/non-nullable)
            $table->string('subject')->after('sender_id'); 
            
            // Ensure the 'content' column is large enough to handle the max length 
            // set in the Filament form (65535 characters). 'longText' is the safest choice.
            // We use 'change()' here in case it was originally defined as a small 'text' field.
            $table->longText('content')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Drop the column we added
            $table->dropColumn('subject');
            
            // If rolling back, revert content back to a standard 'text' field.
            // NOTE: This assumes 'text' was the previous type.
            $table->text('content')->change(); 
        });
    }
};
