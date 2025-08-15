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
    public function index(Request $request): JsonResponse
    {
        $query = PhotobookProduct::where('is_active', true)
            ->with('templates') // Eager load templates
            ->selectRaw('photobook_products.*, COALESCE(order_items_summary.total_sold, 0) as total_sold')
            ->leftJoinSub(
                'SELECT product_id, SUM(quantity) as total_sold FROM photobook_order_items GROUP BY product_id',
                'order_items_summary',
                'photobook_products.id',
                '=',
                'order_items_summary.product_id'
            );

        // Pencarian berdasarkan nama produk
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter berdasarkan harga
        if ($request->has('price_min') && $request->price_min) {
            $query->where('price', '>=', $request->price_min);
        }
        if ($request->has('price_max') && $request->price_max) {
            $query->where('price', '<=', $request->price_max);
        }

        // Sorting
        $sortBy = $request->get('sort', 'name');
        switch ($sortBy) {
            case 'price':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'created_at':
                $query->orderBy('created_at', 'desc');
                break;
            case 'best_selling':
                $query->orderByDesc('total_sold');
                break;
            case 'name':
            default:
                $query->orderBy('name', 'asc');
                break;
        }

        $products = $query->paginate(12); // Pagination untuk performance

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
