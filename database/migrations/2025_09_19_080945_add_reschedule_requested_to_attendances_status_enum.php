<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'reschedule_requested' to the ENUM for the status column
        DB::statement("ALTER TABLE attendances MODIFY COLUMN `status` ENUM('pending', 'approved', 'rejected', 'reschedule_requested') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'reschedule_requested' from the ENUM for the status column
        DB::statement("ALTER TABLE attendances MODIFY COLUMN `status` ENUM('pending', 'approved', 'rejected') NOT NULL");
    }
};
