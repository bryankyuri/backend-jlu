<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Work;
use App\Models\WorkCredit;
use App\Models\WorkGalleryItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortDirection = $filters['sort_direction'] ?? 'desc';
            
            // Validate sort column
            $allowedSortColumns = ['created_at', 'updated_at', 'title', 'client', 'status', 'published_at'];
            if (!in_array($sortBy, $allowedSortColumns)) {
                $sortBy = 'created_at';
            }
            
            $query->orderBy($sortBy, $sortDirection);

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
                'video_project_src' => $validatedData['video_project_src'] ?? null,
                'video_project_poster' => $validatedData['video_project_poster'] ?? null,
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

            // Load relationships for response
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
                'video_project_src' => $validatedData['video_project_src'] ?? null,
                'video_project_poster' => $validatedData['video_project_poster'] ?? null,
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

            // Load relationships for response
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

            // Update the work main data
            $work->update([
                'title' => $validatedData['title'],
                'client' => $validatedData['client'],
                'category' => $validatedData['category'],
                'year' => $validatedData['year'],
                'description' => $validatedData['description'] ?? $work->description,
                'hero_banner_image' => $validatedData['hero_banner_image'] ?? $work->hero_banner_image,
                'video_project_src' => $validatedData['video_project_src'] ?? $work->video_project_src,
                'video_project_poster' => $validatedData['video_project_poster'] ?? $work->video_project_poster,
                'tags' => $validatedData['tags'] ?? $work->tags,
                'status' => $validatedData['status'] ?? $work->status,
                'updated_by' => Auth::id(),
            ]);

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

            // Load relationships for response
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
     * Validate work data.
     */
    private function validateWorkData(Request $request, ?Work $work = null): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'client' => 'required|string|max:255', 
            'category' => 'required|in:film/series,commercial',
            'year' => 'nullable|string|size:4|regex:/^\d{4}$/',
            'description' => 'nullable|string|max:2000',
            'hero_banner_image' => 'nullable|string',
            'video_project_src' => 'nullable|string',
            'video_project_poster' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string|in:MOTION GRAPHIC,COLOR GRADING,VFX,CGI',
            'status' => 'nullable|in:draft,published',
            'credits' => 'nullable|array|min:1',
            'credits.*.role' => 'required|string|max:255',
            'credits.*.names' => 'required|array|min:1',
            'credits.*.names.*' => 'required|string|max:255',
            'credits.*.order' => 'nullable|integer|min:0',
            'gallery_items' => 'nullable|array',
            'gallery_items.*.type' => 'required|in:full-width,2col-full,2col-4:5,compare-full',
            'gallery_items.*.images' => 'required|array|min:1',
            'gallery_items.*.images.*' => 'required|string',
            'gallery_items.*.order' => 'nullable|integer|min:0',
        ];

        // For draft status, make some fields optional
        if ($request->status === 'draft') {
            $rules['hero_banner_image'] = 'nullable|string';
            $rules['video_project_src'] = 'nullable|string';
            $rules['credits'] = 'nullable|array';
            $rules['gallery_items'] = 'nullable|array';
        } else {
            // For published status, ensure required fields
            $rules['hero_banner_image'] = 'required|string';
            $rules['video_project_src'] = 'required|string';
            $rules['credits'] = 'required|array|min:1';
        }

        return $request->validate($rules);
    }

    /**
     * Validate work changes with more flexible rules for incremental updates.
     */
    private function validateWorkChanges(Request $request, ?Work $work = null): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'client' => 'required|string|max:255', 
            'category' => 'required|in:film/series,commercial',
            'year' => 'nullable|string|size:4|regex:/^\d{4}$/',
            'description' => 'nullable|string|max:2000',
            'hero_banner_image' => 'nullable|string',
            'video_project_src' => 'nullable|string',
            'video_project_poster' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string|in:MOTION GRAPHIC,COLOR GRADING,VFX,CGI',
            'status' => 'nullable|in:draft,published',
            'credits' => 'nullable|array',
            'credits.*.role' => 'required|string|max:255',
            'credits.*.names' => 'required|array|min:1',
            'credits.*.names.*' => 'required|string|max:255',
            'credits.*.order' => 'nullable|integer|min:0',
            'gallery_items' => 'nullable|array',
            'gallery_items.*.type' => 'required|in:full-width,2col-full,2col-4:5,compare-full',
            'gallery_items.*.images' => 'required|array|min:1',
            'gallery_items.*.images.*' => 'required|string',
            'gallery_items.*.order' => 'nullable|integer|min:0',
        ];

        // For saveChanges, we're more lenient - all fields are optional except title and client
        // This allows saving partial changes without validation errors

        return $request->validate($rules);
    }
}
