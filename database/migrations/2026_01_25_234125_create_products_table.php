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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('category', ['Crushing, Screening & Processing Equipment', 'Components, Parts & Accessories', 'Structural & Sampling Solutions'])->default('Crushing, Screening & Processing Equipment');
            $table->string('name', 255);
            $table->text('detail_specs')->nullable();
            $table->json('tags')->nullable();
            $table->integer('display_order')->default(0);
            $table->enum('status', ['published', 'draft'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            $table->index('category');
            $table->index('display_order');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
