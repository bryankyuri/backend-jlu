<?php

namespace App\Http\Controllers;

use App\Models\ServiceItem;
use App\Models\ServiceGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ServiceItemController extends Controller
{
    /**
     * List service items with filtering, search, sorting, and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ServiceItem::with('serviceGroup');

            // Filter by status
            if ($request->filled('status') && in_array($request->status, ['active', 'inactive'])) {
                $query->where('status', $request->status);
            }

            // Filter by service group
            if ($request->filled('service_group_id')) {
                $query->where('service_group_id', $request->service_group_id);
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'display_order');
            $sortDirection = $request->input('sort_direction', 'asc');
            
            $allowedSortColumns = ['created_at', 'updated_at', 'title', 'status', 'display_order'];
            if (!in_array($sortBy, $allowedSortColumns)) {
                $sortBy = 'display_order';
            }
            
            if (!in_array(strtolower($sortDirection), ['asc', 'desc'])) {
                $sortDirection = 'asc';
            }
            
            // Special handling for display_order: nulls last
            if ($sortBy === 'display_order') {
                $query->orderByRaw('display_order IS NULL, display_order ' . $sortDirection);
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }

            // Pagination
            $perPage = $request->input('per_page', 100);
            $items = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $items->items(),
                'pagination' => [
                    'total' => $items->total(),
                    'per_page' => $items->perPage(),
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve service items',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create a new service item
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'service_group_id' => 'required|exists:service_groups,id',
                'title' => 'required|string|max:500',
                'description' => 'required|string',
                'status' => 'nullable|in:active,inactive',
                'display_order' => 'nullable|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $item = ServiceItem::create([
                'service_group_id' => $request->service_group_id,
                'title' => $request->title,
                'description' => $request->description,
                'status' => $request->input('status', 'active'),
                'display_order' => $request->display_order,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $item->load('serviceGroup'),
                'message' => 'Service item created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create service item',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get a single service item by ID
     */
    public function show($id): JsonResponse
    {
        try {
            $item = ServiceItem::with('serviceGroup')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $item
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service item not found',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 404);
        }
    }

    /**
     * Update an existing service item
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $item = ServiceItem::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'service_group_id' => 'sometimes|exists:service_groups,id',
                'title' => 'sometimes|string|max:500',
                'description' => 'sometimes|string',
                'status' => 'sometimes|in:active,inactive',
                'display_order' => 'nullable|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $item->update([
                'service_group_id' => $request->input('service_group_id', $item->service_group_id),
                'title' => $request->input('title', $item->title),
                'description' => $request->input('description', $item->description),
                'status' => $request->input('status', $item->status),
                'display_order' => $request->input('display_order', $item->display_order),
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $item->fresh()->load('serviceGroup'),
                'message' => 'Service item updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service item',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete a service item
     */
    public function destroy($id): JsonResponse
    {
        try {
            $item = ServiceItem::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Service item deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete service item',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Reorder service items within a group
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:service_items,id',
            'items.*.display_order' => 'required|integer'
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->items as $itemData) {
                ServiceItem::where('id', $itemData['id'])
                    ->update([
                        'display_order' => $itemData['display_order'],
                        'updated_by' => Auth::id()
                    ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Service items reordered successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder service items',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get all active services grouped by service group (for frontsite)
     */
    public function getPublicServices(): JsonResponse
    {
        try {
            $groups = ServiceGroup::where('status', 'active')
                ->with(['serviceItems' => function ($query) {
                    $query->where('status', 'active')
                          ->orderBy('display_order', 'asc');
                }])
                ->orderBy('display_order', 'asc')
                ->get()
                ->map(function ($group) {
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                        'items' => $group->serviceItems->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'title' => $item->title,
                                'description' => $item->description,
                            ];
                        })
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $groups
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve services',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
