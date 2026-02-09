<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Get all products (CMS - requires auth).
     */
    public function index(Request $request)
    {
        $query = Product::query()->with(['productImages.media']);

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category if provided
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Search by name
        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by tags
        if ($request->has('tags') && is_array($request->tags)) {
            foreach ($request->tags as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'display_order');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        // Pagination
        $perPage = $request->get('per_page', 20);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Create a new product.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => ['required', Rule::in(['Crushing, Screening & Processing Equipment', 'Components, Parts & Accessories', 'Structural & Sampling Solutions'])],
            'name' => 'required|string|max:255',
            'highlight_description' => 'nullable|string',
            'detail_specs' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
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
            $product = Product::create($request->only([
                'category',
                'name',
                'highlight_description',
                'detail_specs',
                'tags',
                'status',
            ]));

            // Attach images if provided
            if ($request->has('image_ids') && is_array($request->image_ids)) {
                foreach ($request->image_ids as $index => $mediaId) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'media_id' => $mediaId,
                        'display_order' => $index + 1,
                    ]);
                }
            }

            DB::commit();

            $product->load(['productImages.media']);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single product by UUID.
     */
    public function show($uuid)
    {
        $product = Product::where('uuid', $uuid)
            ->with(['productImages.media'])
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * Update a product.
     */
    public function update(Request $request, $uuid)
    {
        $product = Product::where('uuid', $uuid)->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'category' => ['required', Rule::in(['Crushing, Screening & Processing Equipment', 'Components, Parts & Accessories', 'Structural & Sampling Solutions'])],
            'name' => 'required|string|max:255',
            'highlight_description' => 'nullable|string',
            'detail_specs' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
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
            $product->update($request->only([
                'category',
                'name',
                'highlight_description',
                'detail_specs',
                'tags',
                'status',
            ]));

            // Update images if provided
            if ($request->has('image_ids')) {
                // Remove old images
                $product->productImages()->delete();

                // Add new images
                if (is_array($request->image_ids)) {
                    foreach ($request->image_ids as $index => $mediaId) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'media_id' => $mediaId,
                            'display_order' => $index + 1,
                        ]);
                    }
                }
            }

            DB::commit();

            $product->load(['productImages.media']);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a product.
     */
    public function destroy($uuid)
    {
        $product = Product::where('uuid', $uuid)->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * Publish a product.
     */
    public function publish($uuid)
    {
        $product = Product::where('uuid', $uuid)->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $product->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product published successfully',
            'data' => $product,
        ]);
    }

    /**
     * Unpublish a product.
     */
    public function unpublish($uuid)
    {
        $product = Product::where('uuid', $uuid)->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $product->update([
            'status' => 'draft',
            'published_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product set to draft',
            'data' => $product,
        ]);
    }

    /**
     * Reorder products.
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.display_order' => 'required|integer|min:1',
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
            foreach ($request->products as $item) {
                Product::where('id', $item['id'])
                    ->update(['display_order' => $item['display_order']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Products reordered successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get public products (for frontsite).
     */
    public function getPublicProducts(Request $request)
    {
        $query = Product::published()
            ->ordered()
            ->with(['productImages.media']);

        // Filter by category if provided
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $products = $query->get();

        // Transform for frontsite compatibility
        $transformed = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'capacity' => $product->capacity,
                'highlight_description' => $product->highlight_description,
                'detailSpecs' => $product->detail_specs,
                'images' => $product->images->pluck('url')->toArray(),
                'tags' => $product->tags ?? [],
                'category' => $product->category,
                'display_order' => $product->display_order,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformed,
        ]);
    }
}
