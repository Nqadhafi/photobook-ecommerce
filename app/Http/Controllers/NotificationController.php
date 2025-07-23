<?php

namespace App\Http\Controllers;

use App\Models\PhotobookNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->photobookNotifications()
            // ->with('order') // Bisa eager load order jika diperlukan
            ->orderBy('created_at', 'desc')
            ->paginate(10); // Pagination untuk skalabilitas

        return response()->json([
            'data' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ]
        ]);
    }

    /**
     * Mark the specified notification as read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PhotobookNotification  $notification (Route Model Binding)
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request, PhotobookNotification $notification): JsonResponse
    {
        // Authorization: Pastikan user memiliki notifikasi ini
        if ($request->user()->id !== $notification->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Update hanya jika belum dibaca
        if (!$notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Notification marked as read']);
    }
}
