<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Media;

class MigrateMediaFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:migrate-files {--dry-run : Show what would be moved without actually moving files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing media files from media/ folder to images/, videos/, and documents/ folders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('Running in DRY RUN mode - no files will be moved');
        }
        
        $this->info('Starting media files migration...');
        
        // Create destination directories if they don't exist
        $this->createDirectories($isDryRun);
        
        // Get all media files that need to be moved
        // Files are currently in media/ folder but database paths have been updated
        $mediaFiles = Media::whereIn('path', ['images/', 'videos/', 'documents/'])->orWhere(function ($query) {
            $query->where('path', 'like', 'images/%')
                  ->orWhere('path', 'like', 'videos/%')
                  ->orWhere('path', 'like', 'documents/%');
        })->get();
        
        if ($mediaFiles->isEmpty()) {
            $this->info('No media files found to migrate.');
            return;
        }
        
        $this->info("Found {$mediaFiles->count()} media files to migrate:");
        
        $movedCount = 0;
        $errorCount = 0;
        
        foreach ($mediaFiles as $media) {
            $result = $this->migrateFileFromMedia($media, $isDryRun);
            
            if ($result['success']) {
                $movedCount++;
                $this->line("✓ {$result['message']}", null, 'v');
            } else {
                $errorCount++;
                $this->line("✗ {$result['message']}", null, 'v');
            }
        }
        
        $this->newLine();
        
        if ($isDryRun) {
            $this->info("DRY RUN COMPLETE:");
            $this->info("- Files that would be moved: {$movedCount}");
            $this->info("- Files with errors: {$errorCount}");
            $this->info('Run without --dry-run flag to perform actual migration');
        } else {
            $this->info("MIGRATION COMPLETE:");
            $this->info("- Files moved successfully: {$movedCount}");
            $this->info("- Files with errors: {$errorCount}");
            
            if ($errorCount > 0) {
                $this->warn("Some files couldn't be moved. Check the output above for details.");
            }
        }
    }
    
    /**
     * Create destination directories
     */
    private function createDirectories($isDryRun)
    {
        $directories = ['images', 'videos', 'documents'];
        
        foreach ($directories as $dir) {
            if (!Storage::disk('public')->exists($dir)) {
                if (!$isDryRun) {
                    Storage::disk('public')->makeDirectory($dir);
                    $this->info("Created directory: storage/app/public/{$dir}");
                } else {
                    $this->info("Would create directory: storage/app/public/{$dir}");
                }
            }
        }
    }
    
    /**
     * Migrate a file from media/ folder to new structure
     */
    private function migrateFileFromMedia($media, $isDryRun)
    {
        // Current path in database (already updated to new structure)
        $newPath = $media->path;
        
        // Actual current location (still in media/ folder)
        $filename = basename($newPath);
        $currentPath = 'media/' . $filename;
        
        // Check if source file exists in media folder
        if (!Storage::disk('public')->exists($currentPath)) {
            return [
                'success' => false,
                'message' => "Source file not found in media folder: {$currentPath}"
            ];
        }
        
        // Check if destination already exists
        if (Storage::disk('public')->exists($newPath)) {
            return [
                'success' => false,
                'message' => "Destination already exists: {$newPath}"
            ];
        }
        
        if (!$isDryRun) {
            // Move the file from media/ to new location
            if (Storage::disk('public')->move($currentPath, $newPath)) {
                // Also move poster file if it exists and is a video
                if ($media->poster_path && str_starts_with($media->mime_type, 'video/')) {
                    $posterFilename = basename($media->poster_path);
                    $currentPosterPath = 'media/' . $posterFilename;
                    
                    if (Storage::disk('public')->exists($currentPosterPath) && 
                        !Storage::disk('public')->exists($media->poster_path)) {
                        Storage::disk('public')->move($currentPosterPath, $media->poster_path);
                    }
                }
                
                return [
                    'success' => true,
                    'message' => "Moved {$media->original_name}: {$currentPath} → {$newPath}"
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Failed to move {$media->original_name}: {$currentPath} → {$newPath}"
                ];
            }
        } else {
            return [
                'success' => true,
                'message' => "Would move {$media->original_name}: {$currentPath} → {$newPath}"
            ];
        }
    }
    
    /**
     * Get destination folder based on MIME type
     */
    private function getDestinationFolder($mimeType)
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'images';
        }
        
        if (str_starts_with($mimeType, 'video/')) {
            return 'videos';
        }
        
        if (in_array($mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ])) {
            return 'documents';
        }
        
        return null;
    }
}
