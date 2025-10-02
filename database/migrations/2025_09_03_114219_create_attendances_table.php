<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['on-schedule', 'additional', 'rescheduled', 'absent']);
            $table->date('scheduled_date');
            $table->date('actual_date')->nullable();
            $table->string('reason')->nullable();
            $table->string('subject');
            $table->string('topic');
            $table->decimal('duration', 4, 2)->default(0); // in hours 
            $table->text('comment1')->nullable();
            $table->text('comment2')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'reschedule_requested'])->default('pending');
            $table->enum('payment_status', ['paid', 'unpaid'])->default('unpaid');
            $table->integer('period_number')->default(1);
            $table->timestamps();
            $table->foreignId('approved_by_id')
                ->nullable()
                ->after('payment_status')
                ->constrained('users')
                ->onDelete('set null');
            $table->enum('session_status', ['present', 'absent', 'late'])->nullable()->after('status');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
