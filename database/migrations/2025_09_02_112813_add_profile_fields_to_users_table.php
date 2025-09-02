<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            // $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('telegram_chat_id')->nullable();
            $table->date('dob')->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->boolean('is_suspended')->default(false);
            $table->enum('region_scope_type', ['city', 'subcity'])->nullable();
            $table->unsignedBigInteger('region_scope_id')->nullable();
            $table->integer('monthly_target_hours')->default(0);
            $table->decimal('salary_per_hour', 8, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
            //   'first_name',
              'middle_name',
              'last_name',
              'phone',
              'telegram_chat_id',
              'dob',
              'profile_photo_path',
              'is_suspended',
              'region_scope_type',
              'region_scope_id',
              'monthly_target_hours',
              'salary_per_hour',
            ]);
        });
    }
};
