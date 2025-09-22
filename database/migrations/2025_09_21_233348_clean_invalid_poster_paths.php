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
        // Clean up invalid poster_path values ('0') to null for proper poster URL generation
        DB::table('media')
            ->where('poster_path', '0')
            ->update([
                'poster_path' => null,
                'poster_filename' => null
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert null values back to '0' if needed
        DB::table('media')
            ->whereNull('poster_path')
            ->where('mime_type', 'like', 'video/%')
            ->update([
                'poster_path' => '0'
            ]);
    }
};
