<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing media file paths to new folder structure
        // Images: media/filename -> images/filename
        // Videos: media/filename -> videos/filename
        // Documents: media/filename -> documents/filename
        
        DB::transaction(function () {
            // Update image paths
            DB::table('media')
                ->where('mime_type', 'like', 'image/%')
                ->where('path', 'like', 'media/%')
                ->update([
                    'path' => DB::raw("REPLACE(path, 'media/', 'images/')")
                ]);
            
            // Update video paths
            DB::table('media')
                ->where('mime_type', 'like', 'video/%')
                ->where('path', 'like', 'media/%')
                ->update([
                    'path' => DB::raw("REPLACE(path, 'media/', 'videos/')")
                ]);
            
            // Update video poster paths (if any exist)
            DB::table('media')
                ->whereNotNull('poster_path')
                ->where('poster_path', 'like', 'media/%')
                ->update([
                    'poster_path' => DB::raw("REPLACE(poster_path, 'media/', 'videos/')")
                ]);
            
            // Update document paths
            DB::table('media')
                ->whereIn('mime_type', [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ])
                ->where('path', 'like', 'media/%')
                ->update([
                    'path' => DB::raw("REPLACE(path, 'media/', 'documents/')")
                ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert paths back to media folder
        DB::transaction(function () {
            // Revert image paths
            DB::table('media')
                ->where('path', 'like', 'images/%')
                ->update([
                    'path' => DB::raw("REPLACE(path, 'images/', 'media/')")
                ]);
            
            // Revert video paths
            DB::table('media')
                ->where('path', 'like', 'videos/%')
                ->update([
                    'path' => DB::raw("REPLACE(path, 'videos/', 'media/')")
                ]);
            
            // Revert video poster paths
            DB::table('media')
                ->whereNotNull('poster_path')
                ->where('poster_path', 'like', 'videos/%')
                ->update([
                    'poster_path' => DB::raw("REPLACE(poster_path, 'videos/', 'media/')")
                ]);
            
            // Revert document paths
            DB::table('media')
                ->where('path', 'like', 'documents/%')
                ->update([
                    'path' => DB::raw("REPLACE(path, 'documents/', 'media/')")
                ]);
        });
    }
};
