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
        Schema::create('work_credits', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to works table
            $table->foreignId('work_id')->constrained('works')->onDelete('cascade');
            
            // Credit information
            $table->string('role');
            $table->json('names'); // Array of names for this role
            
            // Order for displaying credits
            $table->integer('order')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['work_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_credits');
    }
};
