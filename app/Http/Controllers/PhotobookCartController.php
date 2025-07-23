<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PhotobookCart;
use App\Models\PhotobookProduct;
use App\Models\PhotobookTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PhotobookCartController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): JsonResponse
    {
        $carts = $request->user()->photobookCarts()
            ->with(['product', 'template']) // Eager load untuk menghindari N+1 query
            ->get();

        return response()->json([
            'data' => $carts
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request): JsonResponse
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:photobook_products,id',
            'template_id' => 'required|exists:photobook_templates,id',
            'quantity' => 'required|integer|min:1',
            'design_same' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        // Validasi apakah produk dan template aktif
        $product = PhotobookProduct::find($validatedData['product_id']);
        $template = PhotobookTemplate::find($validatedData['template_id']);

        if (!$product->is_active) {
            return response()->json(['error' => 'Product is not active'], 422);
        }

        if (!$template->is_active || $template->product_id !== $product->id) {
             // Tambahkan validasi tambahan: template harus milik produk yang dipilih
            return response()->json(['error' => 'Invalid template for this product'], 422);
        }

        // Buat atau update item di cart
        $cartItem = PhotobookCart::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'product_id' => $validatedData['product_id'],
                'template_id' => $validatedData['template_id'],
            ],
            [
                'quantity' => $validatedData['quantity'],
                'design_same' => $validatedData['design_same'],
                // Jika sudah ada, update quantity dan design_same
                // Jika belum ada, buat baru
            ]
        );

        // Load relasi untuk response
        $cartItem->load(['product', 'template']);

        return response()->json([
            'message' => 'Item added to cart',
            'cart_item' => $cartItem
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PhotobookCart  $photobookCart
     * @return \Illuminate\Http\Response
     */
    public function show(PhotobookCart $photobookCart)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PhotobookCart  $photobookCart
     * @return \Illuminate\Http\Response
     */
    public function edit(PhotobookCart $photobookCart)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PhotobookCart  $photobookCart
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PhotobookCart $photobookCart)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PhotobookCart  $photobookCart
     * @return \Illuminate\Http\Response
     */
    public function destroy(PhotobookCart $cart, Request $request): JsonResponse
    {
        // Authorization: Pastikan user hanya bisa hapus item milik mereka sendiri
        if ($request->user()->id !== $cart->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $cart->delete();

        return response()->json(['message' => 'Item removed from cart']);
    }
}
