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
        // Update all existing gallery items that use 4:5 ratio to 4:3
        DB::table('work_gallery_items')
            ->where('type', 'like', '%-4:5')
            ->update([
                'type' => DB::raw("REPLACE(type, '-4:5', '-4:3')")
            ]);
        
        // Note: The new 1.85:1 ratio types (full-1.85:1, 2col-1.85:1, compare-1.85:1) 
        // are now available for use. No schema changes needed as the column is VARCHAR(50).
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert 4:3 back to 4:5
        DB::table('work_gallery_items')
            ->where('type', 'like', '%-4:3')
            ->update([
                'type' => DB::raw("REPLACE(type, '-4:3', '-4:5')")
            ]);
    }
};
