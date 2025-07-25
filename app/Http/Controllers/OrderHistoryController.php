<?php

namespace App\Http\Controllers;
use App\Models\PhotobookOrder;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class OrderHistoryController extends Controller
{
    //
        /**
     * Display a listing of the user's orders.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()->photobookOrders()
            ->with(['items.product', 'items.template', 'payment'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'data' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ]
        ]);
    }

    /**
     * Display the specified order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PhotobookOrder  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, PhotobookOrder $order): JsonResponse
    {
        // Authorization: Pastikan user memiliki order ini
        if ($request->user()->id !== $order->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Load all related data
        $order->load([
            'items.product',
            'items.template',
            'files',
            'payment',
            'notifications'
        ]);

        return response()->json([
            'data' => $order
        ]);
    }

    /**
     * Get order status timeline.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PhotobookOrder  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function timeline(Request $request, PhotobookOrder $order): JsonResponse
    {
        // Authorization: Pastikan user memiliki order ini
        if ($request->user()->id !== $order->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $timeline = [];

        // Build timeline based on timestamps
        if ($order->created_at) {
            $timeline[] = [
                'status' => 'created',
                'label' => 'Order Created',
                'timestamp' => $order->created_at,
                'description' => 'Your order has been created'
            ];
        }

        if ($order->paid_at) {
            $timeline[] = [
                'status' => 'paid',
                'label' => 'Payment Confirmed',
                'timestamp' => $order->paid_at,
                'description' => 'Your payment has been confirmed'
            ];
        }

        if ($order->file_uploaded_at) {
            $timeline[] = [
                'status' => 'file_uploaded',
                'label' => 'Files Uploaded',
                'timestamp' => $order->file_uploaded_at,
                'description' => 'Your design files have been uploaded'
            ];
        }

        if ($order->processing_at) {
            $timeline[] = [
                'status' => 'processing',
                'label' => 'In Production',
                'timestamp' => $order->processing_at,
                'description' => 'Your order is being processed'
            ];
        }

        if ($order->ready_at) {
            $timeline[] = [
                'status' => 'ready',
                'label' => 'Ready for Pickup',
                'timestamp' => $order->ready_at,
                'description' => 'Your order is ready for pickup',
                'pickup_code' => $order->pickup_code
            ];
        }

        if ($order->completed_at) {
            $timeline[] = [
                'status' => 'completed',
                'label' => 'Order Completed',
                'timestamp' => $order->completed_at,
                'description' => 'Your order has been completed'
            ];
        }

        if ($order->cancelled_at) {
            $timeline[] = [
                'status' => 'cancelled',
                'label' => 'Order Cancelled',
                'timestamp' => $order->cancelled_at,
                'description' => 'Your order has been cancelled'
            ];
        }

        // Sort timeline by timestamp
        usort($timeline, function($a, $b) {
            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
        });

        return response()->json([
            'data' => $timeline
        ]);
    }
}
