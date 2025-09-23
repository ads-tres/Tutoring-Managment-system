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
        // This command adds 'absent' as a valid option to the 'type' ENUM column.
        DB::statement("ALTER TABLE attendances MODIFY COLUMN `type` ENUM('on-schedule', 'additional', 'rescheduled', 'absent') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This command removes 'absent' from the 'type' ENUM column during a rollback.
        DB::statement("ALTER TABLE attendances MODIFY COLUMN `type` ENUM('on-schedule', 'additional', 'rescheduled') NOT NULL");
    }
};
