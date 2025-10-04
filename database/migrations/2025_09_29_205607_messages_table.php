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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            
            // The user who sent the message (always the Manager in this system) 
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            
            $table->string('subject'); 

            // The content of the message
            $table->longText('content')->change();

            // Add a column for sending messages to a single, specific user.
            // It is nullable because messages can still be sent to a 'recipient_target' role.
            $table->foreignId('recipient_user_id')
                  ->nullable()
                  
                  ->constrained('users') 
                  ->onDelete('set null');

            // Make the existing recipient_target nullable, as it will be null for individual messages.
            $table->string('recipient_target')->nullable()->change();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
