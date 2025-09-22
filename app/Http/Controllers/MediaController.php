<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Exception;
use Illuminate\Support\Facades\Log;

class MediaController extends Controller
{
    /**
     * List all media files
     */
    public function index(Request $request)
    {
        try {
            $query = Media::active();

            // Search functionality
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('original_name', 'like', "%{$search}%")
                      ->orWhere('alt_text', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filter by type
            if ($request->has('type') && $request->type) {
                if ($request->type === 'images') {
                    $query->images();
                } elseif ($request->type === 'videos') {
                    $query->videos();
                } else {
                    $query->where('mime_type', 'like', $request->type . '/%');
                }
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            // Pagination
            $perPage = $request->get('per_page', 20);
            $media = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $media,
                'message' => 'Media retrieved successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve media: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload new media file with security validations
     */
    public function upload(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'file' => [
                    'required',
                    'file',
                    'mimes:jpg,jpeg,png,gif,pdf,doc,docx,mp4,mov,avi,webp,svg',
                    'max:20480', // Max 20MB
                ],
                'alt_text' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('file');
            
            // Route to appropriate upload method based on file type
            if (str_starts_with($file->getMimeType(), 'image/')) {
                return $this->uploadImage($request);
            } elseif (str_starts_with($file->getMimeType(), 'video/')) {
                return $this->uploadVideo($request);
            } else {
                // Handle other file types (PDF, docs, etc.) with original logic
                return $this->uploadDocument($request);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload image file to images folder
     */
    public function uploadImage(Request $request)
    {
        try {
            // Validate image-specific request
            $validator = Validator::make($request->all(), [
                'file' => [
                    'required',
                    'file',
                    'mimes:jpg,jpeg,png,gif,webp,svg',
                    'max:10240', // Max 10MB for images
                ],
                'alt_text' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('file');
            
            // Additional security checks
            $allowedMimeTypes = [
                'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'
            ];

            if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid image type detected'
                ], 422);
            }

            // Generate secure filename
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            
            // Store file in images folder
            $path = $file->storeAs('images', $filename, 'public');

            // Create database record
            $media = Media::create([
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
                'extension' => $extension,
                'alt_text' => $request->alt_text,
                'description' => $request->description,
                'poster_path' => null,
                'poster_filename' => null
            ]);

            return response()->json([
                'success' => true,
                'data' => $media,
                'message' => 'Image uploaded successfully'
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Image upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload video file with poster to videos folder
     */
    public function uploadVideo(Request $request)
    {
        try {
            // Validate video-specific request
            $validator = Validator::make($request->all(), [
                'file' => [
                    'required',
                    'file',
                    'mimes:mp4,mov,avi',
                    'max:51200', // Max 50MB for videos
                ],
                'poster' => [
                    'nullable',
                    'file',
                    'mimes:jpg,jpeg,png,webp',
                    'max:5120', // Max 5MB for poster
                ],
                'alt_text' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('file');
            
            // Additional security checks
            $allowedMimeTypes = [
                'video/mp4', 'video/quicktime', 'video/avi'
            ];

            if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid video type detected'
                ], 422);
            }

            // Generate secure filename for video
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            
            // Store video file in videos folder
            $path = $file->storeAs('videos', $filename, 'public');

            // Handle poster upload if provided
            $posterPath = null;
            $posterFilename = null;
            
            if ($request->hasFile('poster')) {
                $posterFile = $request->file('poster');
                $posterExtension = $posterFile->getClientOriginalExtension();
                $posterFilename = 'poster_' . pathinfo($filename, PATHINFO_FILENAME) . '.' . $posterExtension;
                $posterPath = $posterFile->storeAs('videos', $posterFilename, 'public');
            }

            // Create database record
            $media = Media::create([
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
                'extension' => $extension,
                'alt_text' => $request->alt_text,
                'description' => $request->description,
                'poster_path' => $posterPath, // This will be null if no poster
                'poster_filename' => $posterFilename // This will be null if no poster
            ]);

            return response()->json([
                'success' => true,
                'data' => $media,
                'message' => 'Video uploaded successfully'
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Video upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload document files (PDF, DOC, etc.) to documents folder
     */
    public function uploadDocument(Request $request)
    {
        try {
            // Validate document-specific request
            $validator = Validator::make($request->all(), [
                'file' => [
                    'required',
                    'file',
                    'mimes:pdf,doc,docx',
                    'max:20480', // Max 20MB for documents
                ],
                'alt_text' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('file');
            
            // Additional security checks
            $allowedMimeTypes = [
                'application/pdf', 
                'application/msword', 
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];

            if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid document type detected'
                ], 422);
            }

            // Generate secure filename
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            
            // Store file in documents folder
            $path = $file->storeAs('documents', $filename, 'public');

            // Create database record
            $media = Media::create([
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
                'extension' => $extension,
                'alt_text' => $request->alt_text,
                'description' => $request->description,
                'poster_path' => null,
                'poster_filename' => null
            ]);

            return response()->json([
                'success' => true,
                'data' => $media,
                'message' => 'Document uploaded successfully'
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Document upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update video poster with new image
     */
    public function updateVideoPoster(Request $request, $mediaId)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'poster' => [
                    'required',
                    'file',
                    'mimes:jpg,jpeg,png,webp',
                    'max:5120', // Max 5MB for poster
                ]
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find the media record
            $media = Media::find($mediaId);
            
            if (!$media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media not found'
                ], 404);
            }

            // Check if it's a video
            if (!str_contains($media->mime_type, 'video/')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media is not a video file'
                ], 422);
            }

            // Delete old poster if exists
            if ($media->poster_path && Storage::disk('public')->exists($media->poster_path)) {
                Storage::disk('public')->delete($media->poster_path);
            }

            // Upload new poster
            $posterFile = $request->file('poster');
            $posterExtension = $posterFile->getClientOriginalExtension();
            $videoFilename = pathinfo($media->filename, PATHINFO_FILENAME);
            $posterFilename = 'poster_' . $videoFilename . '.' . $posterExtension;
            $posterPath = $posterFile->storeAs('videos', $posterFilename, 'public');

            // Update media record
            $media->update([
                'poster_path' => $posterPath,
                'poster_filename' => $posterFilename,
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Video poster updated successfully',
                'data' => $media->fresh()
            ], 200);

        } catch (Exception $e) {
            Log::error('Video poster update failed: ' . $e->getMessage(), [
                'media_id' => $mediaId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Video poster update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Serve media file publicly
     */
    public function serve($filename)
    {
        try {
            $media = Media::where('filename', $filename)->where('is_active', true)->firstOrFail();
            
            if (!Storage::disk('public')->exists($media->path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            $file = Storage::disk('public')->get($media->path);
            
            return response($file)
                ->header('Content-Type', $media->mime_type)
                ->header('Content-Disposition', 'inline; filename="' . $media->original_name . '"')
                ->header('Cache-Control', 'public, max-age=86400'); // Cache for 1 day

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }
    }

    /**
     * Delete media file
     */
    public function destroy($id)
    {
        try {
            $media = Media::findOrFail($id);
            
            // Delete physical file
            if (Storage::disk('public')->exists($media->path)) {
                Storage::disk('public')->delete($media->path);
            }
            
            // Delete poster file if it exists
            if ($media->poster_path && Storage::disk('public')->exists($media->poster_path)) {
                Storage::disk('public')->delete($media->poster_path);
            }
            
            // Delete database record
            $media->delete();

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update media metadata
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'alt_text' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000',
                'original_name' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $media = Media::findOrFail($id);
            $media->update($request->only(['alt_text', 'description', 'original_name']));

            return response()->json([
                'success' => true,
                'data' => $media,
                'message' => 'Media updated successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update media: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate video thumbnail using FFmpeg
     */
    private function generateVideoThumbnail($videoPath, $videoFilename)
    {
        try {
            // Get the full path to the video file
            $fullVideoPath = Storage::disk('public')->path($videoPath);
            
            // Generate poster filename with prefix
            $posterFilename = 'poster_' . pathinfo($videoFilename, PATHINFO_FILENAME) . '.jpg';
            $posterPath = 'media/' . $posterFilename;
            $fullPosterPath = Storage::disk('public')->path($posterPath);

            // Check if FFmpeg is available
            $ffmpegPath = $this->findFFmpegPath();
            if (!$ffmpegPath) {
                Log::warning('FFmpeg not found, skipping video thumbnail generation');
                return null;
            }

            // Generate thumbnail at 2 seconds into the video
            $command = sprintf(
                '%s -i %s -ss 00:00:02 -vframes 1 -vf "scale=320:240:force_original_aspect_ratio=decrease,pad=320:240:(ow-iw)/2:(oh-ih)/2" -y %s 2>&1',
                escapeshellarg($ffmpegPath),
                escapeshellarg($fullVideoPath),
                escapeshellarg($fullPosterPath)
            );

            exec($command, $output, $returnCode);

            // Check if thumbnail was created successfully
            if ($returnCode === 0 && file_exists($fullPosterPath)) {
                Log::info('Video thumbnail generated successfully', [
                    'video' => $videoFilename,
                    'poster' => $posterFilename
                ]);

                return [
                    'path' => $posterPath,
                    'filename' => $posterFilename
                ];
            } else {
                Log::error('Failed to generate video thumbnail', [
                    'command' => $command,
                    'output' => implode("\n", $output),
                    'return_code' => $returnCode
                ]);
                return null;
            }

        } catch (Exception $e) {
            Log::error('Error generating video thumbnail: ' . $e->getMessage(), [
                'video_path' => $videoPath,
                'video_filename' => $videoFilename
            ]);
            return null;
        }
    }

    /**
     * Find FFmpeg executable path
     */
    private function findFFmpegPath()
    {
        // Common FFmpeg paths
        $possiblePaths = [
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/opt/homebrew/bin/ffmpeg', // macOS with Homebrew
            'C:\\ffmpeg\\bin\\ffmpeg.exe', // Windows
            'ffmpeg' // If in PATH
        ];

        foreach ($possiblePaths as $path) {
            if ($path === 'ffmpeg') {
                // Check if ffmpeg is in PATH
                exec('which ffmpeg 2>/dev/null', $output, $returnCode);
                if ($returnCode === 0 && !empty($output)) {
                    return 'ffmpeg';
                }
                // For Windows, check using 'where'
                exec('where ffmpeg 2>NUL', $output, $returnCode);
                if ($returnCode === 0 && !empty($output)) {
                    return 'ffmpeg';
                }
            } elseif (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
