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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')
            ->constrained('users')
            ->cascadeOnDelete();
            $table->string('full_name');
            $table->string('student_phone')->nullable();
            $table->enum('sex', ['M', 'F']);
            $table->date('dob')->nullable();
            $table->json('initial_skills')->nullable();
            $table->string('father_name')->nullable();
            $table->string('father_phone')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('mother_phone')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('subcity')->nullable();
            $table->string('district')->nullable();
            $table->string('kebele')->nullable();
            $table->string('house_number')->nullable();
            $table->string('street')->nullable();
            $table->string('landmark')->nullable();
            $table->string('school_name')->nullable();
            $table->enum('school_type', ['private','public','international'])->nullable();
            $table->string('grade')->nullable();
            $table->string('frequency')->nullable(); // e.g. JSON or comma-separated
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('session_duration')->nullable();
            $table->enum('status', ['active','inactive'])->default('active');
            $table->date('start_date')->nullable();
            $table->string('student_image')->nullable();
            $table->string('parents_image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
