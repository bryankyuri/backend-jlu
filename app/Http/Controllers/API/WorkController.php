<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Work;
use App\Models\WorkCredit;
use App\Models\WorkGalleryItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WorkController extends Controller
{
    /**
     * Display a listing of works with complex filtering (POST request).
     * This method handles complex filters better than URL parameters.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Work::with(['credits', 'galleryItems']);

            // Get all filter parameters from JSON body
            $filters = $request->all();

            // Filter by status (support multiple values)
            if (!empty($filters['status'])) {
                $statuses = is_array($filters['status']) ? $filters['status'] : [$filters['status']];
                $statuses = array_filter($statuses, function($status) {
                    return in_array($status, ['published', 'unpublished', 'draft']);
                });
                if (!empty($statuses)) {
                    $query->whereIn('status', $statuses);
                }
            }

            // Filter by category
            if (!empty($filters['category']) && $filters['category'] !== 'all') {
                $query->where('category', $filters['category']);
            }

            // Filter by tags (support multiple values) - This handles arrays properly
            if (!empty($filters['tags']) && is_array($filters['tags'])) {
                $tags = array_filter($filters['tags']);
                if (!empty($tags)) {
                    $query->where(function ($q) use ($tags) {
                        foreach ($tags as $tag) {
                            $q->orWhereJsonContains('tags', $tag);
                        }
                    });
                }
            }

            // Filter by year
            if (!empty($filters['year']) && $filters['year'] !== 'all') {
                $query->where('year', $filters['year']);
            }

            // Filter by published status for public API
            if (isset($filters['published']) && $filters['published'] === true) {
                $query->where('status', 'published');
            }

            // Search functionality
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('client', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Apply sorting
            $sortBy = $filters['sort_by'] ?? 'display_order';
            $sortDirection = $filters['sort_direction'] ?? 'desc';
            
            // Validate sort column
            $allowedSortColumns = ['created_at', 'updated_at', 'title', 'client', 'status', 'published_at', 'display_order', 'year'];
            if (!in_array($sortBy, $allowedSortColumns)) {
                $sortBy = 'display_order';
            }
            
            // Special handling for display_order: nulls last
            if ($sortBy === 'display_order') {
                $query->orderByRaw('display_order IS NULL, display_order ' . $sortDirection);
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }

            // Pagination
            $page = $filters['page'] ?? 1;
            $perPage = $filters['per_page'] ?? 15;
            $works = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $works->items(),
                'meta' => [
                    'current_page' => $works->currentPage(),
                    'per_page' => $works->perPage(),
                    'total' => $works->total(),
                    'last_page' => $works->lastPage(),
                ],
                'filters_applied' => $filters, // Debugging: show what filters were applied
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve works',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created work in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate the request data
            $validatedData = $this->validateWorkData($request);

            DB::beginTransaction();

            // Create the work
            $work = Work::create([
                'title' => $validatedData['title'],
                'client' => $validatedData['client'],
                'category' => $validatedData['category'],
                'year' => $validatedData['year'],
                'description' => $validatedData['description'] ?? null,
                'hero_banner_image' => $validatedData['hero_banner_image'] ?? null,
                'hero_banner_position_x' => $validatedData['hero_banner_position_x'] ?? 'center',
                'hero_banner_position_y' => $validatedData['hero_banner_position_y'] ?? 'top',
                'video_project_src' => $validatedData['video_project_src'] ?? null,
                'video_project_poster' => $validatedData['video_project_poster'] ?? null,
                'video_vimeo_url' => $validatedData['video_vimeo_url'] ?? null,
                'video_youtube_url' => $validatedData['video_youtube_url'] ?? null,
                'video_cloudflare_url' => $validatedData['video_cloudflare_url'] ?? null,
                'tags' => $validatedData['tags'] ?? [],
                'status' => $validatedData['status'] ?? 'draft',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Create credits
            if (isset($validatedData['credits']) && is_array($validatedData['credits'])) {
                foreach ($validatedData['credits'] as $creditData) {
                    WorkCredit::create([
                        'work_id' => $work->id,
                        'role' => $creditData['role'],
                        'names' => $creditData['names'],
                        'order' => $creditData['order'] ?? 0,
                    ]);
                }
            }

            // Create gallery items
            if (isset($validatedData['gallery_items']) && is_array($validatedData['gallery_items'])) {
                foreach ($validatedData['gallery_items'] as $galleryData) {
                    WorkGalleryItem::create([
                        'work_id' => $work->id,
                        'type' => $galleryData['type'],
                        'images' => $galleryData['images'],
                        'order' => $galleryData['order'] ?? 0,
                    ]);
                }
            }

            DB::commit();

            // Refresh the model to ensure all auto-generated fields (like slug) are included
            $work->refresh();
            $work->load(['credits', 'galleryItems']);

            return response()->json([
                'success' => true,
                'message' => $work->status === 'published' ? 'Work published successfully' : 'Work saved as draft',
                'data' => $work
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create work',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified work.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $work = Work::with(['credits', 'galleryItems'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $work
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Work not found',
                'error' => config('app.debug') ? $e->getMessage() : 'Work not found'
            ], 404);
        }
    }

    /**
     * Update the specified work in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $work = Work::findOrFail($id);
            
            // Validate the request data
            $validatedData = $this->validateWorkData($request, $work);

            DB::beginTransaction();

            // Update the work
            $work->update([
                'title' => $validatedData['title'],
                'client' => $validatedData['client'],
                'category' => $validatedData['category'],
                'year' => $validatedData['year'],
                'description' => $validatedData['description'] ?? null,
                'hero_banner_image' => $validatedData['hero_banner_image'] ?? null,
                'hero_banner_position_x' => $validatedData['hero_banner_position_x'] ?? $work->hero_banner_position_x,
                'hero_banner_position_y' => $validatedData['hero_banner_position_y'] ?? $work->hero_banner_position_y,
                'video_project_src' => !empty($validatedData['video_project_src']) ? $validatedData['video_project_src'] : null,
                'video_project_poster' => !empty($validatedData['video_project_poster']) ? $validatedData['video_project_poster'] : null,
                'video_vimeo_url' => !empty($validatedData['video_vimeo_url']) ? $validatedData['video_vimeo_url'] : null,
                'video_youtube_url' => !empty($validatedData['video_youtube_url']) ? $validatedData['video_youtube_url'] : null,
                'video_cloudflare_url' => !empty($validatedData['video_cloudflare_url']) ? $validatedData['video_cloudflare_url'] : null,
                'tags' => $validatedData['tags'] ?? [],
                'status' => $validatedData['status'] ?? $work->status,
                'updated_by' => Auth::id(),
            ]);

            // Update credits - delete existing and recreate
            $work->credits()->delete();
            if (isset($validatedData['credits']) && is_array($validatedData['credits'])) {
                foreach ($validatedData['credits'] as $creditData) {
                    WorkCredit::create([
                        'work_id' => $work->id,
                        'role' => $creditData['role'],
                        'names' => $creditData['names'],
                        'order' => $creditData['order'] ?? 0,
                    ]);
                }
            }

            // Update gallery items - delete existing and recreate
            $work->galleryItems()->delete();
            if (isset($validatedData['gallery_items']) && is_array($validatedData['gallery_items'])) {
                foreach ($validatedData['gallery_items'] as $galleryData) {
                    WorkGalleryItem::create([
                        'work_id' => $work->id,
                        'type' => $galleryData['type'],
                        'images' => $galleryData['images'],
                        'order' => $galleryData['order'] ?? 0,
                    ]);
                }
            }

            DB::commit();

            // Refresh the model to get updated slug and load relationships
            $work->refresh();
            $work->load(['credits', 'galleryItems']);

            return response()->json([
                'success' => true,
                'message' => 'Work updated successfully',
                'data' => $work
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update work',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Save changes to the specified work without full recreation.
     * This endpoint is optimized for incremental updates.
     */
    public function saveChanges(Request $request, string $id): JsonResponse
    {
        try {
            $work = Work::findOrFail($id);
            
            // For saveChanges, use more flexible validation
            $validatedData = $this->validateWorkChanges($request, $work);

            DB::beginTransaction();

            // Prepare update data
            $updateData = [
                'title' => $validatedData['title'],
                'client' => $validatedData['client'],
                'category' => $validatedData['category'],
                'year' => $validatedData['year'],
                'description' => $validatedData['description'] ?? $work->description,
                'hero_banner_image' => $validatedData['hero_banner_image'] ?? $work->hero_banner_image,
                'hero_banner_position_x' => $validatedData['hero_banner_position_x'] ?? $work->hero_banner_position_x,
                'hero_banner_position_y' => $validatedData['hero_banner_position_y'] ?? $work->hero_banner_position_y,
                'tags' => $validatedData['tags'] ?? $work->tags,
                'status' => $validatedData['status'] ?? $work->status,
                'updated_by' => Auth::id(),
            ];

            // Handle video fields - convert empty strings to null
            if (array_key_exists('video_project_src', $validatedData)) {
                $updateData['video_project_src'] = empty($validatedData['video_project_src']) ? null : $validatedData['video_project_src'];
            }
            if (array_key_exists('video_project_poster', $validatedData)) {
                $updateData['video_project_poster'] = empty($validatedData['video_project_poster']) ? null : $validatedData['video_project_poster'];
            }
            if (array_key_exists('video_vimeo_url', $validatedData)) {
                $updateData['video_vimeo_url'] = empty($validatedData['video_vimeo_url']) ? null : $validatedData['video_vimeo_url'];
            }
            if (array_key_exists('video_youtube_url', $validatedData)) {
                $updateData['video_youtube_url'] = empty($validatedData['video_youtube_url']) ? null : $validatedData['video_youtube_url'];
            }
            if (array_key_exists('video_cloudflare_url', $validatedData)) {
                $updateData['video_cloudflare_url'] = empty($validatedData['video_cloudflare_url']) ? null : $validatedData['video_cloudflare_url'];
            }

            // Update the work main data
            $work->update($updateData);

            // Update credits only if provided
            if (isset($validatedData['credits']) && is_array($validatedData['credits'])) {
                // Delete existing credits and recreate
                $work->credits()->delete();
                foreach ($validatedData['credits'] as $creditData) {
                    WorkCredit::create([
                        'work_id' => $work->id,
                        'role' => $creditData['role'],
                        'names' => $creditData['names'],
                        'order' => $creditData['order'] ?? 0,
                    ]);
                }
            }

            // Update gallery items only if provided
            if (isset($validatedData['gallery_items']) && is_array($validatedData['gallery_items'])) {
                // Delete existing gallery items and recreate
                $work->galleryItems()->delete();
                foreach ($validatedData['gallery_items'] as $galleryData) {
                    WorkGalleryItem::create([
                        'work_id' => $work->id,
                        'type' => $galleryData['type'],
                        'images' => $galleryData['images'],
                        'order' => $galleryData['order'] ?? 0,
                    ]);
                }
            }

            DB::commit();

            // Refresh the model to get updated slug and load relationships
            $work->refresh();
            $work->load(['credits', 'galleryItems']);

            return response()->json([
                'success' => true,
                'message' => 'Changes saved successfully',
                'data' => $work
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save changes',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified work from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $work = Work::findOrFail($id);
            
            DB::beginTransaction();
            
            // Delete related records (credits and gallery items will be deleted by cascade)
            $work->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete work',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Publish a work.
     */
    public function publish(string $id): JsonResponse
    {
        try {
            $work = Work::findOrFail($id);
            
            if ($work->publish()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Work published successfully',
                    'data' => $work->fresh()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to publish work'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to publish work',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Unpublish a work (make it draft).
     */
    public function unpublish(string $id): JsonResponse
    {
        try {
            $work = Work::findOrFail($id);
            
            if ($work->unpublish()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Work unpublished successfully',
                    'data' => $work->fresh()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to unpublish work'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unpublish work',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Reorder works by updating display_order
     */
    public function reorder(Request $request): JsonResponse
    {
        try {
            // Validation
            $validator = Validator::make($request->all(), [
                'works' => 'required|array|min:1',
                'works.*.id' => 'required|exists:works,id',
                'works.*.display_order' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for duplicate display_order values
            $displayOrders = array_column($request->works, 'display_order');
            if (count($displayOrders) !== count(array_unique($displayOrders))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Duplicate display_order values are not allowed'
                ], 400);
            }

            // Reorder in transaction
            DB::transaction(function () use ($request) {
                // Temporarily set to negative values to avoid conflicts
                foreach ($request->works as $index => $item) {
                    Work::where('id', $item['id'])
                        ->update(['display_order' => -($index + 1)]);
                }
                
                // Set actual positions
                foreach ($request->works as $item) {
                    Work::where('id', $item['id'])
                        ->update(['display_order' => $item['display_order']]);
                }
            });

            // Return updated works
            $workIds = array_column($request->works, 'id');
            $works = Work::with(['credits', 'galleryItems'])
                ->whereIn('id', $workIds)
                ->orderBy('display_order', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $works,
                'message' => 'Works reordered successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder works',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Validate work data.
     */
    private function validateWorkData(Request $request, ?Work $work = null): array
    {
    $rules = [
            'title' => 'required|string|max:255',
            'client' => 'required|string|max:255', 
            'category' => 'required|in:film/series,commercial,music video',
            'year' => 'nullable|string|size:4|regex:/^\d{4}$/',
            'description' => 'nullable|string|max:2000',
            'hero_banner_image' => 'nullable|string',
            'hero_banner_position_x' => 'nullable|string|in:left,center,right',
            'hero_banner_position_y' => 'nullable|string|in:top,center,bottom',
            'video_project_src' => 'nullable|string',
            'video_project_poster' => 'nullable|string',
            'video_vimeo_url' => 'nullable|string|max:255',
            'video_youtube_url' => 'nullable|string|max:255',
            'video_cloudflare_url' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|in:MOTION GRAPHIC,COLOR GRADING,VFX,CGI',
            'status' => 'nullable|in:draft,published',
            'credits' => 'nullable|array|min:1',
            'credits.*.role' => 'required|string|max:255',
            'credits.*.names' => 'required|array|min:1',
            'credits.*.names.*' => 'required|string|max:255',
            'credits.*.order' => 'nullable|integer|min:0',
            'gallery_items' => 'nullable|array',
            'gallery_items.*.type' => 'required|in:full-16:9,full-1.85:1,full-2.35:1,full-2.39:1,full-4:3,2col-16:9,2col-1.85:1,2col-2.35:1,2col-2.39:1,2col-4:3,compare-16:9,compare-1.85:1,compare-2.35:1,compare-2.39:1,compare-4:3',
            'gallery_items.*.images' => 'required|array|min:1',
            'gallery_items.*.images.*' => 'required|string',
            'gallery_items.*.order' => 'nullable|integer|min:0',
        ];

        // For draft status, make some fields optional
        if ($request->status === 'draft') {
            $rules['hero_banner_image'] = 'nullable|string';
            $rules['credits'] = 'nullable|array';
            $rules['gallery_items'] = 'nullable|array';
        } else {
            // For published status, ensure required fields
            $rules['hero_banner_image'] = 'required|string';
            $rules['credits'] = 'required|array|min:1';
        }

        // Validate the basic rules first
        $validated = $request->validate($rules);

        // For published status, ensure at least one video source is provided
        if ($request->status === 'published') {
            $hasVideoSource = !empty($request->video_project_src) ||
                            !empty($request->video_vimeo_url) ||
                            !empty($request->video_youtube_url) ||
                            !empty($request->video_cloudflare_url);

            if (!$hasVideoSource) {
                throw ValidationException::withMessages([
                    'video_sources' => ['At least one video source (Media Gallery, Vimeo, YouTube, or Cloudflare) is required for published works.']
                ]);
            }
        }

        return $validated;
    }

    /**
     * Validate work changes with more flexible rules for incremental updates.
     */
    private function validateWorkChanges(Request $request, ?Work $work = null): array
    {
    $rules = [
            'title' => 'required|string|max:255',
            'client' => 'required|string|max:255', 
            'category' => 'required|in:film/series,commercial,music video',
            'year' => 'nullable|string|size:4|regex:/^\d{4}$/',
            'description' => 'nullable|string|max:2000',
            'hero_banner_image' => 'nullable|string',
            'hero_banner_position_x' => 'nullable|string|in:left,center,right',
            'hero_banner_position_y' => 'nullable|string|in:top,center,bottom',
            'video_project_src' => 'nullable|string',
            'video_project_poster' => 'nullable|string',
            'video_vimeo_url' => 'nullable|string|max:255',
            'video_youtube_url' => 'nullable|string|max:255',
            'video_cloudflare_url' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|in:MOTION GRAPHIC,COLOR GRADING,VFX,CGI',
            'status' => 'nullable|in:draft,published',
            'credits' => 'nullable|array',
            'credits.*.role' => 'required|string|max:255',
            'credits.*.names' => 'required|array|min:1',
            'credits.*.names.*' => 'required|string|max:255',
            'credits.*.order' => 'nullable|integer|min:0',
            'gallery_items' => 'nullable|array',
            'gallery_items.*.type' => 'required|in:full-16:9,full-1.85:1,full-2.35:1,full-2.39:1,full-4:3,2col-16:9,2col-1.85:1,2col-2.35:1,2col-2.39:1,2col-4:3,compare-16:9,compare-1.85:1,compare-2.35:1,compare-2.39:1,compare-4:3',
            'gallery_items.*.images' => 'required|array|min:1',
            'gallery_items.*.images.*' => 'required|string',
            'gallery_items.*.order' => 'nullable|integer|min:0',
        ];

        // For saveChanges, we're more lenient - all fields are optional except title and client
        // This allows saving partial changes without validation errors

        return $request->validate($rules);
    }

    /**
     * Get published works for public display (no authentication required)
     * Supports search, filtering by tags/categories, sorting, and pagination
     */
    public function getPublicWorks(Request $request): JsonResponse
    {
        try {
            $query = Work::with(['credits', 'galleryItems'])
                ->where('status', 'published')
                ->whereNotNull('published_at');

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('client', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filter by tags (support multiple values)
            if ($request->filled('tags')) {
                $tags = $request->input('tags');
                // Convert string to array if needed
                if (is_string($tags)) {
                    $tags = explode(',', $tags);
                }
                $tags = array_filter($tags);
                
                if (!empty($tags)) {
                    $query->where(function ($q) use ($tags) {
                        foreach ($tags as $tag) {
                            $q->orWhereJsonContains('tags', trim($tag));
                        }
                    });
                }
            }

            // Filter by categories (support multiple values)
            if ($request->filled('categories')) {
                $categories = $request->input('categories');
                // Convert string to array if needed
                if (is_string($categories)) {
                    $categories = explode(',', $categories);
                }
                $categories = array_filter($categories);
                
                if (!empty($categories)) {
                    $query->whereIn('category', $categories);
                }
            }

            // Filter by single category (for backward compatibility)
            if ($request->filled('category') && $request->input('category') !== 'all') {
                $query->where('category', $request->input('category'));
            }

            // Filter by year
            if ($request->filled('year')) {
                $query->where('year', $request->input('year'));
            }

            // Filter by client
            if ($request->filled('client')) {
                $query->where('client', 'like', "%{$request->input('client')}%");
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'display_order');
            $sortDirection = $request->input('sort_direction', 'desc');
            
            // Validate sort column for security
            $allowedSortColumns = [
                'published_at', 'created_at', 'updated_at', 'title', 
                'client', 'year', 'category', 'display_order'
            ];
            
            if (!in_array($sortBy, $allowedSortColumns)) {
                $sortBy = 'display_order';
            }
            
            if (!in_array(strtolower($sortDirection), ['asc', 'desc'])) {
                $sortDirection = 'desc';
            }
            
            // Special handling for display_order: nulls last
            if ($sortBy === 'display_order') {
                $query->orderByRaw('display_order IS NULL, display_order ' . $sortDirection);
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }

            // Pagination
            $perPage = min((int) $request->input('per_page', 12), 50); // Max 50 per page
            $page = (int) $request->input('page', 1);
            
            $works = $query->paginate($perPage, ['*'], 'page', $page);

            // Format the response data
            $formattedWorks = $works->map(function ($work) {
                return [
                    'id' => $work->id,
                    'title' => $work->title,
                    'client' => $work->client,
                    'category' => $work->category,
                    'year' => $work->year,
                    'description' => $work->description,
                    'slug' => $work->slug,
                    'hero_banner_image' => $work->hero_banner_image,
                    'video_project_src' => $work->video_project_src,
                    'video_project_poster' => $work->video_project_poster,
                    'video_vimeo_url' => $work->video_vimeo_url,
                    'video_youtube_url' => $work->video_youtube_url,
                    'video_cloudflare_url' => $work->video_cloudflare_url,
                    'tags' => $work->tags ?? [],
                    'published_at' => $work->published_at?->toISOString(),
                    'display_order' => $work->display_order,
                    'credits_count' => $work->credits ? $work->credits->count() : 0,
                    'gallery_items_count' => $work->galleryItems ? $work->galleryItems->count() : 0,
                ];
            });

            // Get unique values for filtering
            $filterData = $this->getPublicFilterData();

            return response()->json([
                'success' => true,
                'data' => $formattedWorks,
                'meta' => [
                    'current_page' => $works->currentPage(),
                    'per_page' => $works->perPage(),
                    'total' => $works->total(),
                    'last_page' => $works->lastPage(),
                    'from' => $works->firstItem(),
                    'to' => $works->lastItem(),
                ],
                'filters' => $filterData,
                'applied_filters' => [
                    'search' => $request->input('search'),
                    'tags' => $request->input('tags'),
                    'categories' => $request->input('categories'),
                    'category' => $request->input('category'),
                    'year' => $request->input('year'),
                    'client' => $request->input('client'),
                    'sort_by' => $sortBy,
                    'sort_direction' => $sortDirection,
                ],
                'message' => 'Works retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to retrieve public works: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve works',
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 12,
                    'total' => 0,
                    'last_page' => 1,
                ],
            ], 500);
        }
    }

    /**
     * Get related works based on tags and category match (no authentication required)
     * Returns published works that share tags or category with the specified work
     */
    public function getRelatedPublicWorks(string $id, Request $request): JsonResponse
    {
        try {
            // First, get the reference work to match against
            $referenceWork = Work::where('status', 'published')
                ->whereNotNull('published_at')
                ->findOrFail($id);

            $query = Work::where('status', 'published')
                ->whereNotNull('published_at')
                ->where('id', '!=', $id); // Exclude the reference work itself

            // Build scoring system for relevance
            $query->selectRaw('*, 
                CASE 
                    WHEN category = ? THEN 10 
                    ELSE 0 
                END as category_score', [$referenceWork->category]);

            // Add tag matching score if reference work has tags
            if (!empty($referenceWork->tags) && is_array($referenceWork->tags)) {
                $tagConditions = [];
                $tagBindings = [];
                
                foreach ($referenceWork->tags as $index => $tag) {
                    $tagConditions[] = "JSON_CONTAINS(tags, ?)";
                    $tagBindings[] = json_encode($tag);
                }
                
                if (!empty($tagConditions)) {
                    $tagConditionString = implode(' + ', array_map(function($condition) {
                        return "CASE WHEN {$condition} THEN 1 ELSE 0 END";
                    }, $tagConditions));
                    
                    $query->selectRaw("*, 
                        CASE 
                            WHEN category = ? THEN 10 
                            ELSE 0 
                        END + ({$tagConditionString}) as relevance_score", 
                        array_merge([$referenceWork->category], $tagBindings)
                    );
                }
            }

            // Filter by category OR shared tags
            $query->where(function ($q) use ($referenceWork) {
                // Match by category
                $q->where('category', $referenceWork->category);
                
                // OR match by tags if reference work has tags
                if (!empty($referenceWork->tags) && is_array($referenceWork->tags)) {
                    $q->orWhere(function ($tagQuery) use ($referenceWork) {
                        foreach ($referenceWork->tags as $tag) {
                            $tagQuery->orWhereJsonContains('tags', $tag);
                        }
                    });
                }
            });

            // Apply sorting and pagination
            $limit = min((int) $request->input('limit', 8), 20); // Max 20 related works
            $sortBy = $request->input('sort_by', 'relevance'); // Default to relevance
            
            if ($sortBy === 'relevance') {
                $query->orderByRaw('relevance_score DESC, published_at DESC');
            } elseif ($sortBy === 'latest') {
                $query->orderBy('published_at', 'desc');
            } elseif ($sortBy === 'year') {
                $query->orderBy('year', 'desc')->orderBy('published_at', 'desc');
            } else {
                // Default fallback
                $query->orderBy('published_at', 'desc');
            }

            $relatedWorks = $query->limit($limit)->get();

            // Format the response data
            $formattedWorks = $relatedWorks->map(function ($work) use ($referenceWork) {
                // Calculate match details
                $categoryMatch = $work->category === $referenceWork->category;
                $tagMatches = [];
                
                if (!empty($referenceWork->tags) && !empty($work->tags)) {
                    $tagMatches = array_intersect($referenceWork->tags, $work->tags);
                }

                return [
                    'id' => $work->id,
                    'title' => $work->title,
                    'client' => $work->client,
                    'category' => $work->category,
                    'year' => $work->year,
                    'description' => $work->description,
                    'slug' => $work->slug,
                    'hero_banner_image' => $work->hero_banner_image,
                    'video_project_src' => $work->video_project_src,
                    'video_project_poster' => $work->video_project_poster,
                    'video_vimeo_url' => $work->video_vimeo_url,
                    'video_youtube_url' => $work->video_youtube_url,
                    'video_cloudflare_url' => $work->video_cloudflare_url,
                    'tags' => $work->tags ?? [],
                    'published_at' => $work->published_at?->toISOString(),
                    'display_order' => $work->display_order,
                    'match_info' => [
                        'category_match' => $categoryMatch,
                        'shared_tags' => array_values($tagMatches),
                        'shared_tags_count' => count($tagMatches),
                        'relevance_score' => $work->relevance_score ?? 0,
                    ],
                ];
            });

            // Group results by match type for better organization
            $categorizedResults = [
                'same_category' => $formattedWorks->filter(function ($work) {
                    return $work['match_info']['category_match'];
                })->values(),
                'shared_tags' => $formattedWorks->filter(function ($work) {
                    return !$work['match_info']['category_match'] && $work['match_info']['shared_tags_count'] > 0;
                })->values(),
                'other_related' => $formattedWorks->filter(function ($work) {
                    return !$work['match_info']['category_match'] && $work['match_info']['shared_tags_count'] === 0;
                })->values(),
            ];

            return response()->json([
                'success' => true,
                'data' => $formattedWorks,
                'categorized' => $categorizedResults,
                'reference_work' => [
                    'id' => $referenceWork->id,
                    'title' => $referenceWork->title,
                    'category' => $referenceWork->category,
                    'tags' => $referenceWork->tags ?? [],
                ],
                'meta' => [
                    'total_found' => $relatedWorks->count(),
                    'limit' => $limit,
                    'sort_by' => $sortBy,
                    'same_category_count' => $categorizedResults['same_category']->count(),
                    'shared_tags_count' => $categorizedResults['shared_tags']->count(),
                    'other_related_count' => $categorizedResults['other_related']->count(),
                ],
                'message' => 'Related works retrieved successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reference work not found or not published',
                'data' => [],
                'categorized' => [
                    'same_category' => [],
                    'shared_tags' => [],
                    'other_related' => [],
                ],
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve related public works: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve related works',
                'data' => [],
                'categorized' => [
                    'same_category' => [],
                    'shared_tags' => [],
                    'other_related' => [],
                ],
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get latest published works sorted by year and updated_at (no authentication required)
     * Returns only works with status 'published' and published_at set
     */
    public function getLatestPublicWorks(Request $request): JsonResponse
    {
        try {
            $query = Work::with(['credits', 'galleryItems'])
                ->where('status', 'published')
                ->whereNotNull('published_at');

            // Apply sorting by year (desc) then by updated_at (desc)
            $query->orderBy('year', 'desc')
                  ->orderBy('updated_at', 'desc');

            // Pagination with configurable limit
            $perPage = min((int) $request->input('per_page', 12), 50); // Max 50 per page
            $page = (int) $request->input('page', 1);
            
            $works = $query->paginate($perPage, ['*'], 'page', $page);

            // Format the response data
            $formattedWorks = $works->map(function ($work) {
                return [
                    'id' => $work->id,
                    'title' => $work->title,
                    'client' => $work->client,
                    'category' => $work->category,
                    'year' => $work->year,
                    'description' => $work->description,
                    'slug' => $work->slug,
                    'hero_banner_image' => $work->hero_banner_image,
                    'video_project_src' => $work->video_project_src,
                    'video_project_poster' => $work->video_project_poster,
                    'video_vimeo_url' => $work->video_vimeo_url,
                    'video_youtube_url' => $work->video_youtube_url,
                    'video_cloudflare_url' => $work->video_cloudflare_url,
                    'tags' => $work->tags ?? [],
                    'published_at' => $work->published_at?->toISOString(),
                    'updated_at' => $work->updated_at?->toISOString(),
                    'display_order' => $work->display_order,
                    'credits_count' => $work->credits ? $work->credits->count() : 0,
                    'gallery_items_count' => $work->galleryItems ? $work->galleryItems->count() : 0,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedWorks,
                'meta' => [
                    'current_page' => $works->currentPage(),
                    'per_page' => $works->perPage(),
                    'total' => $works->total(),
                    'last_page' => $works->lastPage(),
                    'from' => $works->firstItem(),
                    'to' => $works->lastItem(),
                ],
                'sorting' => [
                    'primary' => 'year (desc)',
                    'secondary' => 'updated_at (desc)',
                    'description' => 'Latest works sorted by newest year first, then by most recently updated'
                ],
                'message' => 'Latest works retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to retrieve latest public works: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve latest works',
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 12,
                    'total' => 0,
                    'last_page' => 1,
                ],
            ], 500);
        }
    }

    /**
     * Get published work details for public display (no authentication required)
     * Only returns works with status 'published' and published_at set
     */
    public function getPublicWorkDetail(string $id): JsonResponse
    {
        try {
            $work = Work::with(['credits', 'galleryItems'])
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->findOrFail($id);

            // Format the work data for public consumption
            $formattedWork = [
                'id' => $work->id,
                'title' => $work->title,
                'client' => $work->client,
                'category' => $work->category,
                'year' => $work->year,
                'description' => $work->description,
                'hero_banner_image' => $work->hero_banner_image,
                'hero_banner_position_x' => $work->hero_banner_position_x ?? 'center',
                'hero_banner_position_y' => $work->hero_banner_position_y ?? 'top',
                'video_project_src' => $work->video_project_src,
                'video_project_poster' => $work->video_project_poster,
                'video_vimeo_url' => $work->video_vimeo_url,
                'video_youtube_url' => $work->video_youtube_url,
                'video_cloudflare_url' => $work->video_cloudflare_url,
                'tags' => $work->tags ?? [],
                'status' => $work->status,
                'slug' => $work->slug,
                'meta_description' => $work->meta_description,
                'display_order' => $work->display_order,
                'published_at' => $work->published_at?->toISOString(),
                'created_at' => $work->created_at?->toISOString(),
                'updated_at' => $work->updated_at?->toISOString(),
                'credits' => $work->credits ? $work->credits->map(function ($credit) {
                    return [
                        'id' => $credit->id,
                        'role' => $credit->role,
                        'names' => $credit->names, // Use correct field name (array)
                        'order' => $credit->order,
                        'work_id' => $credit->work_id,
                        'created_at' => $credit->created_at,
                        'updated_at' => $credit->updated_at,
                    ];
                }) : [],
                'gallery_items' => $work->galleryItems ? $work->galleryItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'type' => $item->type,
                        'images' => $item->images, // Use correct field name (array)
                        'order' => $item->order,
                        'caption' => $item->caption,
                        'description' => $item->description,
                        'work_id' => $item->work_id,
                        'created_at' => $item->created_at,
                        'updated_at' => $item->updated_at,
                    ];
                }) : [],
                'credits_count' => $work->credits ? $work->credits->count() : 0,
                'gallery_items_count' => $work->galleryItems ? $work->galleryItems->count() : 0,
            ];

            // Get related works (same category, published, excluding current work)
            $relatedWorks = Work::where('category', $work->category)
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('id', '!=', $work->id)
                ->orderBy('published_at', 'desc')
                ->limit(4)
                ->get(['id', 'title', 'client', 'hero_banner_image', 'slug', 'category'])
                ->map(function ($relatedWork) {
                    return [
                        'id' => $relatedWork->id,
                        'title' => $relatedWork->title,
                        'client' => $relatedWork->client,
                        'hero_banner_image' => $relatedWork->hero_banner_image,
                        'slug' => $relatedWork->slug,
                        'category' => $relatedWork->category,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $formattedWork,
                'related_works' => $relatedWorks,
                'message' => 'Work details retrieved successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Work not found or not published',
                'data' => null,
                'related_works' => []
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve public work detail: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve work details',
                'data' => null,
                'related_works' => [],
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get filter data for public works (categories, tags, years, etc.)
     */
    private function getPublicFilterData(): array
    {
        try {
            $publishedWorks = Work::where('status', 'published')
                ->whereNotNull('published_at')
                ->get();

            // Get unique categories
            $categories = $publishedWorks->pluck('category')
                ->filter()
                ->unique()
                ->values()
                ->sort()
                ->all();

            // Get unique tags
            $allTags = $publishedWorks->pluck('tags')
                ->filter()
                ->flatten()
                ->unique()
                ->values()
                ->sort()
                ->all();

            // Get unique years
            $years = $publishedWorks->pluck('year')
                ->filter()
                ->unique()
                ->values()
                ->sort()
                ->reverse()
                ->all();

            // Get unique clients
            $clients = $publishedWorks->pluck('client')
                ->filter()
                ->unique()
                ->values()
                ->sort()
                ->all();

            return [
                'categories' => $categories,
                'tags' => $allTags,
                'years' => $years,
                'clients' => $clients,
                'sort_options' => [
                    ['value' => 'published_at', 'label' => 'Published Date'],
                    ['value' => 'title', 'label' => 'Title'],
                    ['value' => 'client', 'label' => 'Client'],
                    ['value' => 'year', 'label' => 'Year'],
                    ['value' => 'category', 'label' => 'Category'],
                ],
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to get filter data: ' . $e->getMessage());
            return [
                'categories' => [],
                'tags' => [],
                'years' => [],
                'clients' => [],
                'sort_options' => [],
            ];
        }
    }
}
