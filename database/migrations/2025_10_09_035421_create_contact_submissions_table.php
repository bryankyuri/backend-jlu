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
        Schema::create('contact_submissions', function (Blueprint $table) {
            $table->id();
            $table->enum('form_type', ['career', 'pitch', 'produce'])->notNull();
            $table->string('name')->notNull();
            $table->string('email')->notNull();
            $table->text('message')->notNull();
            
            // Career form specific fields
            $table->string('subject')->nullable();
            $table->text('portfolio_link')->nullable();
            
            // Pitch form specific fields  
            $table->text('document_link')->nullable();
            
            // Produce form specific fields
            $table->string('company_name')->nullable();
            
            // Security & tracking fields
            $table->text('recaptcha_token')->nullable();
            $table->decimal('recaptcha_score', 3, 2)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            // Admin management fields
            $table->enum('status', ['pending', 'reviewed', 'responded', 'archived'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->unsignedBigInteger('responded_by')->nullable();
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('form_type');
            $table->index('status');
            $table->index('created_at');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_submissions');
    }
};
