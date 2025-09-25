<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if foreign keys exist before dropping them
        try {
            Schema::table('work_credits', function (Blueprint $table) {
                $table->dropForeign(['work_id']);
            });
        } catch (Exception $e) {
            // Foreign key doesn't exist, continue
        }
        
        try {
            Schema::table('work_gallery_items', function (Blueprint $table) {
                $table->dropForeign(['work_id']);
            });
        } catch (Exception $e) {
            // Foreign key doesn't exist, continue
        }

        // Step 1: Add UUID column to works table
        Schema::table('works', function (Blueprint $table) {
            $table->uuid('new_id')->nullable();
        });

        // Step 2: Generate UUIDs for existing records
        DB::table('works')->get()->each(function ($work) {
            DB::table('works')
                ->where('id', $work->id)
                ->update(['new_id' => (string) \Illuminate\Support\Str::uuid()]);
        });

        // Step 3: Add UUID columns to related tables
        Schema::table('work_credits', function (Blueprint $table) {
            $table->uuid('new_work_id')->nullable();
        });

        Schema::table('work_gallery_items', function (Blueprint $table) {
            $table->uuid('new_work_id')->nullable();
        });

        // Step 4: Update related tables with UUIDs
        $works = DB::table('works')->get();
        foreach ($works as $work) {
            DB::table('work_credits')
                ->where('work_id', $work->id)
                ->update(['new_work_id' => $work->new_id]);
            
            DB::table('work_gallery_items')
                ->where('work_id', $work->id)
                ->update(['new_work_id' => $work->new_id]);
        }

        // Step 5: Drop old primary key and rename columns in works table
        Schema::table('works', function (Blueprint $table) {
            $table->dropPrimary(['id']);
            $table->dropColumn('id');
        });

        Schema::table('works', function (Blueprint $table) {
            $table->renameColumn('new_id', 'id');
        });

        Schema::table('works', function (Blueprint $table) {
            $table->primary('id');
        });

        // Step 6: Update related tables - drop old columns and rename new ones
        Schema::table('work_credits', function (Blueprint $table) {
            $table->dropColumn('work_id');
        });

        Schema::table('work_credits', function (Blueprint $table) {
            $table->renameColumn('new_work_id', 'work_id');
        });

        Schema::table('work_gallery_items', function (Blueprint $table) {
            $table->dropColumn('work_id');
        });

        Schema::table('work_gallery_items', function (Blueprint $table) {
            $table->renameColumn('new_work_id', 'work_id');
        });

        // Step 7: Add foreign key constraints
        Schema::table('work_credits', function (Blueprint $table) {
            $table->foreign('work_id')->references('id')->on('works')->onDelete('cascade');
        });

        Schema::table('work_gallery_items', function (Blueprint $table) {
            $table->foreign('work_id')->references('id')->on('works')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a complex rollback - for safety, we'll create a new table approach
        // Note: This rollback will lose data relationships due to UUID->INT conversion
        
        // Drop foreign key constraints
        try {
            Schema::table('work_credits', function (Blueprint $table) {
                $table->dropForeign(['work_id']);
            });
        } catch (Exception $e) {
            // Continue if foreign key doesn't exist
        }
        
        try {
            Schema::table('work_gallery_items', function (Blueprint $table) {
                $table->dropForeign(['work_id']);
            });
        } catch (Exception $e) {
            // Continue if foreign key doesn't exist
        }

        // Add temporary integer ID column to works
        Schema::table('works', function (Blueprint $table) {
            $table->bigIncrements('new_id');
        });

        // Convert back to integer IDs (this will break existing relationships)
        Schema::table('works', function (Blueprint $table) {
            $table->dropPrimary(['id']);
            $table->dropColumn('id');
        });

        Schema::table('works', function (Blueprint $table) {
            $table->renameColumn('new_id', 'id');
        });

        // Recreate foreign key constraints (these will be broken)
        Schema::table('work_credits', function (Blueprint $table) {
            $table->unsignedBigInteger('work_id')->change();
            $table->foreign('work_id')->references('id')->on('works')->onDelete('cascade');
        });

        Schema::table('work_gallery_items', function (Blueprint $table) {
            $table->unsignedBigInteger('work_id')->change();
            $table->foreign('work_id')->references('id')->on('works')->onDelete('cascade');
        });
    }
};
