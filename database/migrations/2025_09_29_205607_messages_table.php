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
            
            // The content of the message
            $table->text('content');
            
            // Recipient target: stores the specific recipient identifier. 
            // Examples: 'user:123', 'role:parent', 'role:tutor', 'ALL_USERS'
            $table->string('recipient_target')->index();
            
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
