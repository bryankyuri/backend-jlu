<?php

namespace App\Http\Controllers;

use App\Models\VideoBanner;
use App\Models\Work;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class VideoBannerController extends Controller
{
    /**
     * Display a listing of video banners
     */
    public function index(): JsonResponse
    {
        try {
            $banners = VideoBanner::with(['work:id,title,client,tags,hero_banner_image,video_project_poster,slug'])
                ->active()
                ->ordered()
                ->get()
                ->map(function ($banner) {
                    return [
                        'id' => $banner->id,
                        'work_id' => $banner->work_id,
                        'work_title' => $banner->work->title ?? null,
                        'work_client' => $banner->work->client ?? null,
                        'work_slug' => $banner->work->slug ?? null,
                        'work_categories' => $banner->work->tags ?? [],
                        'video_url' => $banner->video_url,
                        'video_thumbnail' => $banner->video_thumbnail_url, // Use the computed attribute
                        'video_source_type' => $banner->video_source_type, // Add video source type
                        'is_custom_video' => $banner->is_custom_video,
                        'position' => $banner->position,
                        'created_at' => $banner->created_at,
                        'updated_at' => $banner->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $banners,
                'message' => 'Video banners retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve video banners',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created video banner
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validation rules
            $validator = Validator::make($request->all(), [
                'work_id' => 'required|exists:works,id',
                'video_url' => 'required|string|max:255',
                'video_thumbnail' => 'nullable|string|max:255',
                'is_custom_video' => 'required|boolean',
                'position' => 'nullable|integer|min:1|max:4'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if we already have 4 active banners
            $activeBannersCount = VideoBanner::active()->count();
            if ($activeBannersCount >= 4) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum 4 video banners allowed',
                    'error' => 'BANNER_LIMIT_EXCEEDED'
                ], 400);
            }

            // Create the banner
            $banner = VideoBanner::create($request->only([
                'work_id',
                'video_url',
                'video_thumbnail',
                'is_custom_video',
                'position'
            ]));

            // Load the work relationship
            $banner->load(['work:id,title,client,tags,hero_banner_image,video_project_poster,slug']);

            // Format response
            $formattedBanner = [
                'id' => $banner->id,
                'work_id' => $banner->work_id,
                'work_title' => $banner->work->title ?? null,
                'work_client' => $banner->work->client ?? null,
                'work_slug' => $banner->work->slug ?? null,
                'work_categories' => $banner->work->tags ?? [],
                'video_url' => $banner->video_url,
                'video_thumbnail' => $banner->video_thumbnail_url, // Use the computed attribute
                'video_source_type' => $banner->video_source_type, // Add video source type
                'is_custom_video' => $banner->is_custom_video,
                'position' => $banner->position,
                'created_at' => $banner->created_at,
                'updated_at' => $banner->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $formattedBanner,
                'message' => 'Video banner created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create video banner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified video banner
     */
    public function show(string $id): JsonResponse
    {
        try {
            $banner = VideoBanner::with(['work:id,title,client,tags,hero_banner_image,video_project_poster,slug'])->findOrFail($id);

            $formattedBanner = [
                'id' => $banner->id,
                'work_id' => $banner->work_id,
                'work_title' => $banner->work->title ?? null,
                'work_client' => $banner->work->client ?? null,
                'work_slug' => $banner->work->slug ?? null,
                'work_categories' => $banner->work->tags ?? [],
                'video_url' => $banner->video_url,
                'video_thumbnail' => $banner->video_thumbnail_url, // Use the computed attribute
                'video_source_type' => $banner->video_source_type, // Add video source type
                'is_custom_video' => $banner->is_custom_video,
                'position' => $banner->position,
                'is_active' => $banner->is_active,
                'created_at' => $banner->created_at,
                'updated_at' => $banner->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $formattedBanner,
                'message' => 'Video banner retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Video banner not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified video banner
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $banner = VideoBanner::findOrFail($id);

            // Validation rules
            $validator = Validator::make($request->all(), [
                'work_id' => 'sometimes|exists:works,id',
                'video_url' => 'sometimes|string|max:255',
                'video_thumbnail' => 'nullable|string|max:255',
                'is_custom_video' => 'sometimes|boolean',
                'position' => 'sometimes|integer|min:1|max:4'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update the banner
            $banner->update($request->only([
                'work_id',
                'video_url',
                'video_thumbnail',
                'is_custom_video',
                'position'
            ]));

            // Load the work relationship
            $banner->load(['work:id,title,client,tags,hero_banner_image,video_project_poster,slug']);

            // Format response
            $formattedBanner = [
                'id' => $banner->id,
                'work_id' => $banner->work_id,
                'work_title' => $banner->work->title ?? null,
                'work_client' => $banner->work->client ?? null,
                'work_slug' => $banner->work->slug ?? null,
                'work_categories' => $banner->work->tags ?? [],
                'video_url' => $banner->video_url,
                'video_thumbnail' => $banner->video_thumbnail_url, // Use the computed attribute
                'video_source_type' => $banner->video_source_type, // Add video source type
                'is_custom_video' => $banner->is_custom_video,
                'position' => $banner->position,
                'created_at' => $banner->created_at,
                'updated_at' => $banner->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $formattedBanner,
                'message' => 'Video banner updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update video banner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified video banner
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $banner = VideoBanner::findOrFail($id);
            $banner->delete();

            return response()->json([
                'success' => true,
                'message' => 'Video banner deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete video banner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder video banners
     */
    public function reorder(Request $request): JsonResponse
    {
        try {
            // Validation rules
            $validator = Validator::make($request->all(), [
                'banners' => 'required|array|max:4',
                'banners.*.id' => 'required|exists:video_banners,id',
                'banners.*.position' => 'required|integer|min:1|max:4'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for duplicate positions
            $positions = array_column($request->banners, 'position');
            if (count($positions) !== count(array_unique($positions))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Duplicate positions are not allowed'
                ], 400);
            }

            // Reorder the banners
            VideoBanner::reorderBanners($request->banners);

            // Get updated banners
            $banners = VideoBanner::with(['work:id,title,client,tags,hero_banner_image,video_project_poster,slug'])
                ->active()
                ->ordered()
                ->get()
                ->map(function ($banner) {
                    return [
                        'id' => $banner->id,
                        'work_id' => $banner->work_id,
                        'work_title' => $banner->work->title ?? null,
                        'work_client' => $banner->work->client ?? null,
                        'work_slug' => $banner->work->slug ?? null,
                        'work_categories' => $banner->work->tags ?? [],
                        'video_url' => $banner->video_url,
                        'video_thumbnail' => $banner->video_thumbnail_url, // Use the computed attribute
                        'video_source_type' => $banner->video_source_type, // Add video source type
                        'is_custom_video' => $banner->is_custom_video,
                        'position' => $banner->position,
                        'created_at' => $banner->created_at,
                        'updated_at' => $banner->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $banners,
                'message' => 'Video banners reordered successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder video banners',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get video banners for public display (no authentication required)
     */
    public function getPublicBanners(): JsonResponse
    {
        try {
            $banners = VideoBanner::with(['work:id,title,client,tags,hero_banner_image,video_project_poster,slug'])
                ->active()
                ->ordered()
                ->get()
                ->map(function ($banner) {
                    return [
                        'id' => $banner->id,
                        'work_id' => $banner->work_id,
                        'title' => $banner->work->title ?? 'Untitled Project',
                        'client' => $banner->work->client ?? 'Unknown Client',
                        'slug' => $banner->work->slug ?? null,
                        'categories' => $banner->work->tags ?? [],
                        'video_url' => $banner->video_url,
                        'video_thumbnail' => $banner->video_thumbnail_url, // Use the computed attribute
                        'video_source_type' => $banner->video_source_type, // Add video source type
                        'is_custom_video' => $banner->is_custom_video,
                        'position' => $banner->position,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $banners,
                'count' => $banners->count(),
                'message' => 'Video banners retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to retrieve public video banners: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve video banners',
                'data' => [],
                'count' => 0
            ], 500);
        }
    }
}