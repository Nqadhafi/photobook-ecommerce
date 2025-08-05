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
            'search' => 'nullable|string|max:255', // Untuk pencarian berdasarkan nama
        ]);

        $query = PhotobookProduct::query();

        // Filter berdasarkan pencarian nama
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
            'name' => 'required|string|max:255|unique:photobook_products,name', // Sesuaikan nama tabel
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Validasi file gambar
            // Tambahkan validasi untuk field lain jika ada
        ]);

        try {
            // Handle upload thumbnail jika ada
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                // Simpan ke disk 'public' di folder 'products/thumbnails'
                // Anda bisa menggantinya dengan disk R2 jika sudah siap
                $thumbnailPath = $request->file('thumbnail')->store('products/thumbnails', 'public');
                $validated['thumbnail'] = $thumbnailPath; // Simpan path ke validated data
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
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PhotobookProduct  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, PhotobookProduct $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:photobook_products,name,' . $product->id, // Abaikan ID produk ini
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Validasi file gambar
            // Tambahkan validasi untuk field lain jika ada
        ]);

        try {
            // Handle upload thumbnail jika ada file baru
            if ($request->hasFile('thumbnail')) {
                // Hapus thumbnail lama jika ada
                if ($product->thumbnail) {
                    Storage::disk('public')->delete($product->thumbnail);
                }
                // Simpan thumbnail baru
                $thumbnailPath = $request->file('thumbnail')->store('products/thumbnails', 'public');
                $validated['thumbnail'] = $thumbnailPath;
            }

            $product->update($validated);

            return response()->json($product);
        } catch (\Exception $e) {
            Log::error('Failed to update product ID ' . $product->id . ': ' . $e->getMessage(), ['validated_data' => $validated]);
            return response()->json(['error' => 'Failed to update product. Please try again.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PhotobookProduct  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(PhotobookProduct $product): JsonResponse
    {
        try {
            // Hapus thumbnail terkait jika ada
            if ($product->thumbnail) {
                Storage::disk('public')->delete($product->thumbnail);
            }

            $product->delete();

            return response()->json(['message' => 'Product deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Failed to delete product ID ' . $product->id . ': ' . $e->getMessage());
            
            // Tangani error khusus jika produk memiliki relasi yang mencegah penghapusan
            if ($e->getCode() == 23000) { // SQLSTATE[23000]: Integrity constraint violation
                 return response()->json(['error' => 'Cannot delete product because it is associated with orders or other data.'], 400);
            }
            
            return response()->json(['error' => 'Failed to delete product. Please try again.'], 500);
        }
    }
}
