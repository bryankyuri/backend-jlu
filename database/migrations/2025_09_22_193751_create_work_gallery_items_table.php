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
        Schema::create('work_gallery_items', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to works table
            $table->foreignId('work_id')->constrained('works')->onDelete('cascade');
            
            // Gallery item information
            $table->enum('type', ['full-width', '2col-full', '2col-4:5', 'compare-full']);
            $table->json('images'); // Array of image URLs for this gallery item
            
            // Order for displaying gallery items
            $table->integer('order')->default(0);
            
            // Optional metadata
            $table->string('caption')->nullable();
            $table->text('description')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['work_id', 'order']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_gallery_items');
    }
};
