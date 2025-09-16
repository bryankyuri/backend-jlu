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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('filename'); // Generated secure filename
            $table->string('original_name'); // Original uploaded filename
            $table->string('mime_type');
            $table->unsignedBigInteger('size'); // File size in bytes
            $table->string('path'); // Storage path
            $table->string('extension', 10);
            $table->string('alt_text')->nullable(); // For images
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes for performance
            $table->index('mime_type');
            $table->index('extension');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
