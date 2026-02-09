<?php

namespace App\Http\Controllers;

use App\Models\ServiceGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ServiceGroupController extends Controller
{
    /**
     * Display a listing of service groups
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ServiceGroup::query();
            
            // Filter by status
            if ($request->has('status') && in_array($request->status, ['active', 'inactive'])) {
                $query->where('status', $request->status);
            }
            
            // Search by name
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            
            // Sorting
            $sortBy = $request->input('sort_by', 'display_order');
            $sortDirection = $request->input('sort_direction', 'asc');
            
            $allowedSortColumns = ['created_at', 'updated_at', 'name', 'status', 'display_order'];
            if (!in_array($sortBy, $allowedSortColumns)) {
                $sortBy = 'display_order';
            }
            
            $query->orderBy($sortBy, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 100);
            $groups = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $groups->items(),
                'pagination' => [
                    'total' => $groups->total(),
                    'per_page' => $groups->perPage(),
                    'current_page' => $groups->currentPage(),
                    'last_page' => $groups->lastPage()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve service groups',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created service group
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'display_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive'
        ]);

        try {
            $group = ServiceGroup::create([
                'name' => $request->name,
                'display_order' => $request->display_order,
                'status' => $request->status,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Service group created successfully',
                'data' => $group
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create service group',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified service group
     */
    public function show($id): JsonResponse
    {
        try {
            $group = ServiceGroup::with('serviceItems')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $group
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service group not found',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 404);
        }
    }

    /**
     * Update the specified service group
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:200',
            'display_order' => 'nullable|integer',
            'status' => 'sometimes|required|in:active,inactive'
        ]);

        try {
            $group = ServiceGroup::findOrFail($id);
            
            $group->update([
                'name' => $request->name ?? $group->name,
                'display_order' => $request->display_order ?? $group->display_order,
                'status' => $request->status ?? $group->status,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Service group updated successfully',
                'data' => $group->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service group',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified service group
     * Only allowed if no items are mapped to this group
     */
    public function destroy($id): JsonResponse
    {
        try {
            $group = ServiceGroup::findOrFail($id);
            
            // Check if group has items
            if ($group->serviceItems()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete group with existing service items. Please delete or reassign the items first.'
                ], 422);
            }
            
            $group->delete();

            return response()->json([
                'success' => true,
                'message' => 'Service group deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Reorder service groups
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'groups' => 'required|array',
            'groups.*.id' => 'required|exists:service_groups,id',
            'groups.*.display_order' => 'required|integer'
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->groups as $groupData) {
                ServiceGroup::where('id', $groupData['id'])
                    ->update([
                        'display_order' => $groupData['display_order'],
                        'updated_by' => Auth::id()
                    ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Service groups reordered successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder service groups',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get public service groups (active only) with their items
     */
    public function getPublicGroups(): JsonResponse
    {
        try {
            $groups = ServiceGroup::where('status', 'active')
                ->with(['serviceItems' => function ($query) {
                    $query->where('status', 'active')
                          ->orderBy('display_order', 'asc');
                }])
                ->orderBy('display_order', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $groups
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve service groups',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
