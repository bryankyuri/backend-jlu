<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Exception;

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
            
            // Additional security checks
            $allowedMimeTypes = [
                'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
                'application/pdf', 
                'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'video/mp4', 'video/quicktime', 'video/avi'
            ];

            if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file type detected'
                ], 422);
            }

            // Generate secure filename
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            
            // Store file in public storage for public access
            $path = $file->storeAs('media', $filename, 'public');

            // Create database record
            $media = Media::create([
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
                'extension' => $extension,
                'alt_text' => $request->alt_text,
                'description' => $request->description
            ]);

            return response()->json([
                'success' => true,
                'data' => $media,
                'message' => 'File uploaded successfully'
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
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
}
