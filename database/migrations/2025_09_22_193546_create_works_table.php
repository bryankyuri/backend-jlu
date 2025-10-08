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
        Schema::create('works', function (Blueprint $table) {
            $table->id();
            
            // Basic information
            $table->string('title');
            $table->string('client');
            $table->enum('category', ['film/series', 'commercial']);
            $table->string('year', 4)->nullable();
            $table->text('description')->nullable();
            
            // Media files
            $table->string('hero_banner_image')->nullable();
            $table->string('video_project_src')->nullable();
            $table->string('video_project_poster')->nullable();
            $table->string('video_vimeo_url')->nullable();
            $table->string('video_youtube_url')->nullable();
            $table->string('video_cloudflare_url')->nullable();
            
            // Tags (stored as JSON array)
            $table->json('tags')->nullable();
            
            // Status management
            $table->enum('status', ['draft', 'published'])->default('draft');
            
            // SEO and metadata
            $table->string('slug')->unique()->nullable();
            $table->text('meta_description')->nullable();
            
            // Order for displaying
            $table->integer('display_order')->nullable();
            
            // User who created/last updated
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Published timestamp (different from created_at)
            $table->timestamp('published_at')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status', 'published_at']);
            $table->index(['category', 'status']);
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('works');
    }
};
