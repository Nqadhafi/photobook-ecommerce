<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\PhotobookProduct;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
        ]);

        // Query default tidak akan mengambil produk yang sudah di-soft-delete
        $query = PhotobookProduct::query();
        if (!empty($validated['search'])) {
            $query->where('name', 'like', '%' . $validated['search'] . '%');
        }

        $perPage = $validated['per_page'] ?? 15;
        $products = $query->latest()->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:photobook_products,name',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                $thumbnailPath = $request->file('thumbnail')->store('products/thumbnails', 'public');
                $validated['thumbnail'] = $thumbnailPath;
            }

            // Set default is_active jika tidak disediakan
            if (!isset($validated['is_active'])) {
                $validated['is_active'] = true;
            }

            $product = PhotobookProduct::create($validated);

            return response()->json($product, 201);
        } catch (\Exception $e) {
            Log::error('Failed to create product: ' . $e->getMessage(), ['validated_data' => $validated]);
            return response()->json(['error' => 'Failed to create product. Please try again.'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PhotobookProduct  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(PhotobookProduct $product): JsonResponse
    {
        return response()->json($product);
    }

    /**
     * Update the specified resource in storage.
     * Izinkan perubahan semua field. Perubahan tidak mempengaruhi order item yang sudah ada.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PhotobookProduct  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, PhotobookProduct $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:photobook_products,name,' . $product->id,
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            if ($request->hasFile('thumbnail')) {
                if ($product->thumbnail) {
                    Storage::disk('public')->delete($product->thumbnail);
                }
                $thumbnailPath = $request->file('thumbnail')->store('products/thumbnails', 'public');
                $validated['thumbnail'] = $thumbnailPath;
            }

            $product->update($validated);

            // Kembalikan data produk yang diperbarui
            // Order item yang sudah ada akan terus menggunakan harga lama mereka
            return response()->json($product);
        } catch (\Exception $e) {
            Log::error('Failed to update product ID ' . $product->id . ': ' . $e->getMessage(), ['validated_data' => $validated]);
            return response()->json(['error' => 'Failed to update product. Please try again.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage (Soft Delete).
     *
     * @param  \App\Models\PhotobookProduct  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(PhotobookProduct $product): JsonResponse
    {
        try {
            // Soft delete: Mengisi kolom deleted_at
            $product->delete();

            Log::info("Product ID {$product->id} ({$product->name}) marked as deleted by admin user ID " . auth()->id());

            return response()->json(['message' => 'Product marked as deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Failed to delete product ID ' . $product->id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete product. Please try again.'], 500);
        }
    }
}