<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
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
            $table->string('map_location', 500)->nullable();
            $table->string('school_name')->nullable();
            $table->enum('school_type', ['private', 'public', 'international'])->nullable();
            $table->string('grade')->nullable();
            $table->string('frequency')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('session_duration')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->date('start_date')->nullable();
            $table->string('student_image')->nullable();
            $table->string('parents_image')->nullable();
            $table->timestamps();
            $table->unsignedSmallInteger('session_length_minutes')->nullable()->after('start_time');
            $table->json('scheduled_days')->nullable();
            $table->foreignId('tutor_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('users')
                ->onDelete('set null');
            $table->unsignedInteger('sessions_per_period')->default(12)->after('status');
            $table->decimal('price_per_session', 8, 2)->default(0);
            if (Schema::hasColumn('students', 'session_rate')) {
                $table->renameColumn('session_rate', 'price_per_period');
            } else if (!Schema::hasColumn('students', 'price_per_period')) {
                $table->decimal('price_per_period', 8, 2)->default(100);
            }
            $table->decimal('balance', 10, 2)->default(0);
            $table->date('period_start')->nullable();
            $table->boolean('period_closed')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('students');
    }
};
