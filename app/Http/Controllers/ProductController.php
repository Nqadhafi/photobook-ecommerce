<?php

namespace App\Http\Controllers;
use App\Models\PhotobookProduct;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\PhotobookTemplate;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $products = PhotobookProduct::where('is_active', true)
            ->with('templates') // Eager load templates
            ->paginate(12); // Pagination untuk performance

        return response()->json([
            'data' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        ]);
    }

    /**
     * Display the specified product.
     *
     * @param  \App\Models\PhotobookProduct  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(PhotobookProduct $product): JsonResponse
    {
        if (!$product->is_active) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Load product with templates
        $product->load('templates');

        return response()->json([
            'data' => $product
        ]);
    }

    /**
     * Display templates for a specific product.
     *
     * @param  \App\Models\PhotobookProduct  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function templates(PhotobookProduct $product): JsonResponse
    {
        if (!$product->is_active) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $templates = $product->templates()
            ->where('is_active', true)
            ->paginate(12);

        return response()->json([
            'data' => $templates->items(),
            'pagination' => [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
            ]
        ]);
    }
}
