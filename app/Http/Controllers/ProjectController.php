<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    /**
     * Get all projects (CMS - requires auth).
     */
    public function index(Request $request)
    {
        $query = Project::query()->with('projectImages.media');

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Order by display_order
        $query->orderBy('display_order', 'asc');

        $projects = $query->get();

        return response()->json([
            'success' => true,
            'data' => $projects,
        ]);
    }

    /**
     * Create a new project.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'year' => 'nullable|string|max:255',
            'description_en' => 'nullable|string',
            'description_id' => 'nullable|string',
            'status' => 'required|in:draft,published',
            'image_ids' => 'nullable|array|max:10',
            'image_ids.*' => 'exists:media,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $project = Project::create($request->only([
                'title',
                'location',
                'year',
                'description',
                'status',
            ]));

            // Attach images if provided
            if ($request->has('image_ids') && is_array($request->image_ids)) {
                foreach ($request->image_ids as $index => $mediaId) {
                    ProjectImage::create([
                        'project_id' => $project->id,
                        'media_id' => $mediaId,
                        'display_order' => $index + 1,
                    ]);
                }
            }

            DB::commit();

            $project->load('projectImages.media');

            return response()->json([
                'success' => true,
                'message' => 'Project created successfully',
                'data' => $project,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create project',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single project by UUID.
     */
    public function show($uuid)
    {
        $project = Project::where('uuid', $uuid)
            ->with('projectImages.media')
            ->first();

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $project,
        ]);
    }

    /**
     * Update a project.
     */
    public function update(Request $request, $uuid)
    {
        $project = Project::where('uuid', $uuid)->first();

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'year' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published',
            'image_ids' => 'nullable|array|max:10',
            'image_ids.*' => 'exists:media,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $project->update($request->only([
                'title',
                'location',
                'year',
                'description',
                'status',
            ]));

            // Update images if provided
            if ($request->has('image_ids')) {
                // Delete existing images
                $project->projectImages()->delete();

                // Add new images
                if (is_array($request->image_ids)) {
                    foreach ($request->image_ids as $index => $mediaId) {
                        ProjectImage::create([
                            'project_id' => $project->id,
                            'media_id' => $mediaId,
                            'display_order' => $index + 1,
                        ]);
                    }
                }
            }

            DB::commit();

            $project->load('projectImages.media');

            return response()->json([
                'success' => true,
                'message' => 'Project updated successfully',
                'data' => $project,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update project',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publish a project.
     */
    public function publish($uuid)
    {
        $project = Project::where('uuid', $uuid)->first();

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
            ], 404);
        }

        $project->update([
            'status' => 'published',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project published successfully',
            'data' => $project,
        ]);
    }

    /**
     * Unpublish a project (set to draft).
     */
    public function unpublish($uuid)
    {
        $project = Project::where('uuid', $uuid)->first();

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
            ], 404);
        }

        $project->update([
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project set to draft successfully',
            'data' => $project,
        ]);
    }

    /**
     * Reorder projects.
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'projects' => 'required|array',
            'projects.*.uuid' => 'required|exists:projects,uuid',
            'projects.*.display_order' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($request->projects as $projectData) {
                Project::where('uuid', $projectData['uuid'])
                    ->update(['display_order' => $projectData['display_order']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Projects reordered successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder projects',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get published projects for public website.
     */
    public function getPublicProjects()
    {
        $projects = Project::published()
            ->with('projectImages.media')
            ->orderBy('display_order', 'asc')
            ->get()
            ->map(function ($project) {
                // Get images directly from projectImages relationship
                $images = $project->projectImages
                    ->map(function ($projectImage) {
                        return $projectImage->media ? $projectImage->media->url : null;
                    })
                    ->filter() // Remove nulls
                    ->values() // Reset array keys
                    ->toArray();

                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'location' => $project->location,
                    'year' => $project->year,
                    'description' => $project->description,
                    'images' => $images,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $projects,
        ]);
    }
}
