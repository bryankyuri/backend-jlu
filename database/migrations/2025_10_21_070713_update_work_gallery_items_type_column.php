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
        // Change the type column from ENUM to VARCHAR to support all aspect ratio types
        DB::statement("ALTER TABLE `work_gallery_items` MODIFY COLUMN `type` VARCHAR(50) NOT NULL");
        
        // Update existing old type values to new format (if any exist)
        DB::table('work_gallery_items')->where('type', 'full-width')->update(['type' => 'full-16:9']);
        DB::table('work_gallery_items')->where('type', '2col-full')->update(['type' => '2col-16:9']);
        DB::table('work_gallery_items')->where('type', 'compare-full')->update(['type' => 'compare-16:9']);
        // '2col-4:5' already has correct format, no update needed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to old ENUM type
        DB::statement("ALTER TABLE `work_gallery_items` MODIFY COLUMN `type` ENUM('full-width', '2col-full', '2col-4:5', 'compare-full') NOT NULL");
        
        // Revert type values back to old format
        DB::table('work_gallery_items')->where('type', 'full-16:9')->update(['type' => 'full-width']);
        DB::table('work_gallery_items')->where('type', '2col-16:9')->update(['type' => '2col-full']);
        DB::table('work_gallery_items')->where('type', 'compare-16:9')->update(['type' => 'compare-full']);
    }
};
