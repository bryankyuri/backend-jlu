<?php

namespace App\Http\Controllers;

use App\Models\Showreel;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ShowreelController extends Controller
{
    /**
     * Display a listing of showreels (Admin)
     */
    public function index(): JsonResponse
    {
        try {
            $showreels = Showreel::with('posterMedia:id,filename,path,mime_type')
                ->ordered()
                ->get()
                ->map(function ($showreel) {
                    return [
                        'id' => $showreel->id,
                        'title' => $showreel->title,
                        'description' => $showreel->description,
                        'video_url' => $showreel->video_url,
                        'video_source_type' => $showreel->video_source_type,
                        'video_youtube_url' => $showreel->video_youtube_url,
                        'video_vimeo_url' => $showreel->video_vimeo_url,
                        'video_cloudflare_url' => $showreel->video_cloudflare_url,
                        'video_embed_url' => $showreel->video_embed_url,
                        'poster_media_id' => $showreel->poster_media_id,
                        'poster_url' => $showreel->poster_url,
                        'duration' => $showreel->duration,
                        'position' => $showreel->position,
                        'published_date' => $showreel->published_date?->format('Y-m-d'),
                        'created_at' => $showreel->created_at,
                        'updated_at' => $showreel->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $showreels,
                'message' => 'Showreels retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve showreels', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve showreels',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created showreel
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'video_url' => 'required|string',
                'video_source_type' => 'required|in:upload,youtube,vimeo',
                'video_youtube_url' => 'nullable|url',
                'video_vimeo_url' => 'nullable|url',
                'video_cloudflare_url' => 'nullable|url',
                'poster_media_id' => 'required|exists:media,id', // Made mandatory
                'position' => 'nullable|integer', // Removed min:1, position will be auto-assigned if not provided
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Auto-assign position if not provided or invalid
            $maxPosition = Showreel::max('position') ?? 0;
            $position = $request->filled('position') && $request->position >= 1 
                ? $request->position 
                : $maxPosition + 1;

            $showreel = Showreel::create([
                'title' => $request->title,
                'description' => $request->description,
                'video_url' => $request->video_url,
                'video_source_type' => $request->video_source_type,
                'video_youtube_url' => $request->video_youtube_url,
                'video_vimeo_url' => $request->video_vimeo_url,
                'video_cloudflare_url' => $request->video_cloudflare_url,
                'poster_media_id' => $request->poster_media_id,
                'position' => $position,
            ]);

            $showreel->load('posterMedia');

            Log::info('Showreel created', ['showreel_id' => $showreel->id]);

            return response()->json([
                'success' => true,
                'data' => $showreel,
                'message' => 'Showreel created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create showreel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create showreel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified showreel
     */
    public function show(string $id): JsonResponse
    {
        try {
            $showreel = Showreel::with('posterMedia')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $showreel,
                'message' => 'Showreel retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Showreel not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified showreel
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $showreel = Showreel::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'video_url' => 'sometimes|required|string',
                'video_source_type' => 'sometimes|required|in:upload,youtube,vimeo',
                'video_youtube_url' => 'nullable|url',
                'video_vimeo_url' => 'nullable|url',
                'video_cloudflare_url' => 'nullable|url',
                'poster_media_id' => 'required|exists:media,id', // Made mandatory
                'position' => 'nullable|integer', // Removed min:1 for consistency
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $showreel->update($request->only([
                'title',
                'description',
                'video_url',
                'video_source_type',
                'video_youtube_url',
                'video_vimeo_url',
                'video_cloudflare_url',
                'poster_media_id',
                'position',
            ]));

            $showreel->load('posterMedia');

            Log::info('Showreel updated', ['showreel_id' => $showreel->id]);

            return response()->json([
                'success' => true,
                'data' => $showreel,
                'message' => 'Showreel updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update showreel', [
                'showreel_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update showreel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified showreel
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $showreel = Showreel::findOrFail($id);
            $showreel->delete();

            Log::info('Showreel deleted', ['showreel_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Showreel deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete showreel', [
                'showreel_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete showreel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder showreels
     */
    public function reorder(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'showreels' => 'required|array',
                'showreels.*.id' => 'required|exists:showreels,id',
                'showreels.*.position' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            foreach ($request->showreels as $item) {
                Showreel::where('id', $item['id'])->update(['position' => $item['position']]);
            }

            Log::info('Showreels reordered', ['count' => count($request->showreels)]);

            return response()->json([
                'success' => true,
                'message' => 'Showreels reordered successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reorder showreels', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder showreels',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get public showreels (for frontend website)
     */
    public function getPublicShowreels(): JsonResponse
    {
        try {
            $showreels = Showreel::with('posterMedia:id,filename,path,mime_type')
                ->ordered()
                ->get()
                ->map(function ($showreel) {
                    return [
                        'id' => $showreel->id,
                        'title' => $showreel->title,
                        'description' => $showreel->description,
                        'video_url' => $showreel->video_url,
                        'video_source_type' => $showreel->video_source_type,
                        'video_embed_url' => $showreel->video_embed_url,
                        'poster_url' => $showreel->poster_url,
                        'duration' => $showreel->duration,
                        'position' => $showreel->position,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $showreels
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve public showreels', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve showreels',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
