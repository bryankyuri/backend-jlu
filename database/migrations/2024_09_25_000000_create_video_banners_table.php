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
        Schema::create('video_banners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('work_id');
            $table->string('video_url');
            $table->string('video_thumbnail')->nullable();
            $table->boolean('is_custom_video')->default(false);
            $table->integer('position')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('work_id')->references('id')->on('works')->onDelete('cascade');
            
            // Index for performance
            $table->index('work_id');
            $table->index('position');
            $table->index('is_active');
            
            // Unique constraint to ensure no duplicate positions for active banners
            $table->unique(['position', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_banners');
    }
};