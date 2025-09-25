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
        // Step 1: Create a mapping table to store old ID to new UUID relationships
        Schema::create('works_uuid_mapping', function (Blueprint $table) {
            $table->unsignedBigInteger('old_id');
            $table->uuid('new_id');
            $table->primary('old_id');
        });

        // Step 2: Generate UUIDs for all existing works
        $works = DB::table('works')->get();
        foreach ($works as $work) {
            $uuid = (string) \Illuminate\Support\Str::uuid();
            DB::table('works_uuid_mapping')->insert([
                'old_id' => $work->id,
                'new_id' => $uuid
            ]);
        }

        // Step 3: Create new works table with UUID primary key
        Schema::create('works_new', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
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

        // Step 4: Copy data from old works table to new works table with UUID
        foreach ($works as $work) {
            $mapping = DB::table('works_uuid_mapping')->where('old_id', $work->id)->first();
            
            DB::table('works_new')->insert([
                'id' => $mapping->new_id,
                'title' => $work->title,
                'client' => $work->client,
                'category' => $work->category,
                'year' => $work->year,
                'description' => $work->description,
                'hero_banner_image' => $work->hero_banner_image,
                'video_project_src' => $work->video_project_src,
                'video_project_poster' => $work->video_project_poster,
                'tags' => $work->tags,
                'status' => $work->status,
                'slug' => $work->slug,
                'meta_description' => $work->meta_description,
                'display_order' => $work->display_order,
                'created_by' => $work->created_by,
                'updated_by' => $work->updated_by,
                'published_at' => $work->published_at,
                'created_at' => $work->created_at,
                'updated_at' => $work->updated_at,
            ]);
        }

        // Step 5: Drop foreign key constraints from related tables
        Schema::table('work_credits', function (Blueprint $table) {
            $table->dropForeign(['work_id']);
        });
        
        Schema::table('work_gallery_items', function (Blueprint $table) {
            $table->dropForeign(['work_id']);
        });

        // Step 6: Update related tables with UUIDs
        foreach (DB::table('works_uuid_mapping')->get() as $mapping) {
            DB::table('work_credits')
                ->where('work_id', $mapping->old_id)
                ->update(['work_id' => $mapping->new_id]);
                
            DB::table('work_gallery_items')
                ->where('work_id', $mapping->old_id)
                ->update(['work_id' => $mapping->new_id]);
        }

        // Step 7: Change column types in related tables
        Schema::table('work_credits', function (Blueprint $table) {
            $table->uuid('work_id')->change();
        });

        Schema::table('work_gallery_items', function (Blueprint $table) {
            $table->uuid('work_id')->change();
        });

        // Step 8: Drop old works table and rename new one
        Schema::dropIfExists('works');
        Schema::rename('works_new', 'works');

        // Step 9: Recreate foreign key constraints
        Schema::table('work_credits', function (Blueprint $table) {
            $table->foreign('work_id')->references('id')->on('works')->onDelete('cascade');
        });

        Schema::table('work_gallery_items', function (Blueprint $table) {
            $table->foreign('work_id')->references('id')->on('works')->onDelete('cascade');
        });

        // Step 10: Clean up mapping table
        Schema::dropIfExists('works_uuid_mapping');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This rollback is complex and will lose data integrity
        // For safety, we recommend backing up data before running this migration
        
        Schema::table('work_credits', function (Blueprint $table) {
            $table->dropForeign(['work_id']);
        });
        
        Schema::table('work_gallery_items', function (Blueprint $table) {
            $table->dropForeign(['work_id']);
        });

        // Create old-style works table
        Schema::create('works_old', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('client');
            $table->enum('category', ['film/series', 'commercial']);
            $table->string('year', 4)->nullable();
            $table->text('description')->nullable();
            $table->string('hero_banner_image')->nullable();
            $table->string('video_project_src')->nullable();
            $table->string('video_project_poster')->nullable();
            $table->json('tags')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->string('slug')->unique()->nullable();
            $table->text('meta_description')->nullable();
            $table->integer('display_order')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at']);
            $table->index(['category', 'status']);
            $table->index('display_order');
        });

        // Copy data back (this will break relationships)
        $counter = 1;
        foreach (DB::table('works')->get() as $work) {
            DB::table('works_old')->insert([
                'id' => $counter++,
                'title' => $work->title,
                'client' => $work->client,
                'category' => $work->category,
                'year' => $work->year,
                'description' => $work->description,
                'hero_banner_image' => $work->hero_banner_image,
                'video_project_src' => $work->video_project_src,
                'video_project_poster' => $work->video_project_poster,
                'tags' => $work->tags,
                'status' => $work->status,
                'slug' => $work->slug,
                'meta_description' => $work->meta_description,
                'display_order' => $work->display_order,
                'created_by' => $work->created_by,
                'updated_by' => $work->updated_by,
                'published_at' => $work->published_at,
                'created_at' => $work->created_at,
                'updated_at' => $work->updated_at,
            ]);
        }

        Schema::dropIfExists('works');
        Schema::rename('works_old', 'works');

        // Update related tables back to integer (this will break all existing relationships)
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
