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
        Schema::create('showreels', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_url'); // Primary video URL from media library
            $table->string('video_source_type')->default('upload'); // 'upload', 'youtube', 'vimeo'
            $table->string('video_youtube_url')->nullable(); // Optional YouTube URL
            $table->string('video_vimeo_url')->nullable(); // Optional Vimeo URL
            $table->string('video_cloudflare_url')->nullable(); // Optional Cloudflare Stream URL
            $table->unsignedBigInteger('poster_media_id')->nullable(); // Foreign key to media table
            $table->integer('position')->default(1); // Display order
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Foreign key for poster from media library
            $table->foreign('poster_media_id')->references('id')->on('media')->onDelete('set null');
            
            // Indexes for performance
            $table->index('is_active');
            $table->index('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('showreels');
    }
};
