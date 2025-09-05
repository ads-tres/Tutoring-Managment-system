<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // In the migration file:
public function up(): void
{
    Schema::table('students', function (Blueprint $table) {
        $table->json('scheduled_days')->nullable()->after('status');
        $table->time('start_time')->nullable()->after('scheduled_days');
        $table->unsignedSmallInteger('session_length_minutes')->nullable()->after('start_time');
    });
}

public function down(): void
{
    Schema::table('students', function (Blueprint $table) {
        $table->dropColumn(['scheduled_days', 'start_time', 'session_length_minutes']);
    });
}

};
