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
            // Add a column for sending messages to a single, specific user.
            // It is nullable because messages can still be sent to a 'recipient_target' role.
            $table->foreignId('recipient_user_id')
                  ->nullable()
                  ->after('sender_id')
                  ->constrained('users') // Assumes your users table is named 'users'
                  ->onDelete('set null');

            // Make the existing recipient_target nullable, as it will be null for individual messages.
            $table->string('recipient_target')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Drop the foreign key and the column
            $table->dropForeign(['recipient_user_id']);
            $table->dropColumn('recipient_user_id');

            // Revert recipient_target back to non-nullable if necessary (assuming it was originally required)
            // You might need to adjust this if you know the original schema constraints.
            // For now, we'll leave it nullable on rollback to avoid breaking existing data.
            // $table->string('recipient_target')->nullable(false)->change();
        });
    }
};
