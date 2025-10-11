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
        // Creates the 'exports' table required by Filament's export feature.
        if (!Schema::hasTable('exports')) {
            Schema::create('exports', function (Blueprint $table) {
                // Primary key for the export record
                $table->id();

                // Foreign key to the user who initiated the export
                // Note: The 'user_id' can be nullable if anonymous exports are allowed
                $table->foreignId('user_id')->nullable()->index();

                // The fully qualified class name of the Exporter (e.g., App\Filament\Exports\StudentExporter)
                $table->string('exporter', 255);

                // Optional: The name of the file generated
                $table->string('file_name', 255)->nullable();

                // The disk where the export file is stored (e.g., 'local', 's3')
                $table->string('disk', 255);

                // Counters for rows processed
                $table->integer('total_rows')->nullable();
                $table->integer('successful_rows')->default(0);
                $table->integer('failed_rows')->default(0);

                // Status of the export job: 'waiting', 'in_progress', 'completed', 'failed'
                $table->string('state', 255)->default('waiting');

                // Progress percentage (0-100)
                $table->integer('progress')->default(0);

                // Timestamps for creation and update
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drops the table if the migration is rolled back
        Schema::dropIfExists('exports');
    }
};
