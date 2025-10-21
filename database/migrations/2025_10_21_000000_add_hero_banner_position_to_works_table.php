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
        Schema::table('works', function (Blueprint $table) {
            // Add hero banner position columns after hero_banner_image
            $table->string('hero_banner_position_x', 50)->default('center')->after('hero_banner_image');
            $table->string('hero_banner_position_y', 50)->default('top')->after('hero_banner_position_x');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->dropColumn(['hero_banner_position_x', 'hero_banner_position_y']);
        });
    }
};
