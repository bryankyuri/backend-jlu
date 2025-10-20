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
        Schema::table('showreels', function (Blueprint $table) {
            // First, drop the existing foreign key
            $table->dropForeign(['poster_media_id']);
            
            // Make poster_media_id required (not nullable)
            $table->unsignedBigInteger('poster_media_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint
            // Changed from onDelete('set null') to onDelete('cascade') since it's now required
            $table->foreign('poster_media_id')->references('id')->on('media')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('showreels', function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign(['poster_media_id']);
            
            // Make poster_media_id nullable again
            $table->unsignedBigInteger('poster_media_id')->nullable()->change();
            
            // Re-add the foreign key with set null behavior
            $table->foreign('poster_media_id')->references('id')->on('media')->onDelete('set null');
        });
    }
};
