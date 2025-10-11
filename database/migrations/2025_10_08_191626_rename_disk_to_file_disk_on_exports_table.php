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
        // We use Schema::table() to modify an existing table.
        Schema::table('exports', function (Blueprint $table) {
            // Check if the 'disk' column exists before renaming it
            if (Schema::hasColumn('exports', 'disk')) {
                $table->renameColumn('disk', 'file_disk');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We revert the change if the migration is rolled back.
        Schema::table('exports', function (Blueprint $table) {
            // Check if the 'file_disk' column exists before renaming it back
            if (Schema::hasColumn('exports', 'file_disk')) {
                $table->renameColumn('file_disk', 'disk');
            }
        });
    }
};
