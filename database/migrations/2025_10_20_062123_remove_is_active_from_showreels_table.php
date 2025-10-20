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
            // Drop the is_active index first
            $table->dropIndex(['is_active']);
            
            // Remove the is_active column
            $table->dropColumn('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('showreels', function (Blueprint $table) {
            // Add back the is_active column
            $table->boolean('is_active')->default(true)->after('position');
            
            // Add back the index
            $table->index('is_active');
        });
    }
};
