<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * MANUAL MIGRATION SQL (if needed):
     * ALTER TABLE works MODIFY COLUMN category ENUM('film/series', 'commercial', 'music video') NOT NULL;
     */
    public function up(): void
    {
        // For MySQL, we need to alter the enum column to add the new value
        DB::statement("ALTER TABLE works MODIFY COLUMN category ENUM('film/series', 'commercial', 'music video') NOT NULL");
    }

    /**
     * Reverse the migrations.
     * 
     * MANUAL ROLLBACK SQL (if needed):
     * ALTER TABLE works MODIFY COLUMN category ENUM('film/series', 'commercial') NOT NULL;
     */
    public function down(): void
    {
        // Revert back to the original enum values
        // WARNING: This will fail if any records have 'music video' as category
        DB::statement("ALTER TABLE works MODIFY COLUMN category ENUM('film/series', 'commercial') NOT NULL");
    }
};
