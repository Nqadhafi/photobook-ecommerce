<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Deskprint; // Model Deskprint yang sudah dibuat
use Illuminate\Support\Facades\Log;

class AdminDeskprintController extends Controller
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
            'is_active' => 'nullable|boolean', // Untuk filter berdasarkan status aktif
        ]);

        $query = Deskprint::query();

        // Filter berdasarkan pencarian nama
        if (!empty($validated['search'])) {
            $query->where('name', 'like', '%' . $validated['search'] . '%');
        }

        // Filter berdasarkan status aktif
        if (isset($validated['is_active'])) {
            $query->where('is_active', $validated['is_active']);
        }

        $perPage = $validated['per_page'] ?? 15;
        $deskprints = $query->latest()->paginate($perPage);

        return response()->json($deskprints);
    }

    /**
     * Store a newly created resource in storage.
     * Digunakan oleh Super Admin untuk membuat deskprint baru.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:deskprints,name', // Pastikan nama unik
            'location' => 'nullable|string|max:255',
            'contact_number' => 'required|string|max:20', // Sesuaikan dengan format nomor yang diinginkan
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean', // Default akan diambil dari model jika tidak ada
        ]);

        try {
            $deskprint = Deskprint::create($validated);

            return response()->json($deskprint, 201);
        } catch (\Exception $e) {
            Log::error('Failed to create deskprint: ' . $e->getMessage(), ['validated_data' => $validated]);
            return response()->json(['error' => 'Failed to create deskprint. Please try again.'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Deskprint  $deskprint
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Deskprint $deskprint): JsonResponse
    {
        return response()->json($deskprint);
    }

    /**
     * Update the specified resource in storage.
     * Digunakan oleh Super Admin untuk mengubah deskprint.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Deskprint  $deskprint
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Deskprint $deskprint): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:deskprints,name,' . $deskprint->id, // Abaikan ID ini
            'location' => 'nullable|string|max:255',
            'contact_number' => 'sometimes|required|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $deskprint->update($validated);

            return response()->json($deskprint);
        } catch (\Exception $e) {
            Log::error('Failed to update deskprint ID ' . $deskprint->id . ': ' . $e->getMessage(), ['validated_data' => $validated]);
            return response()->json(['error' => 'Failed to update deskprint. Please try again.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Digunakan oleh Super Admin untuk menghapus deskprint.
     *
     * @param  \App\Models\Deskprint  $deskprint
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Deskprint $deskprint): JsonResponse
    {
        try {
            // Mencegah penghapusan jika deskprint sedang digunakan (misalnya, ada order yang dikirim ke sini)
            // Anda bisa menambahkan logika pengecekan relasi di sini jika diperlukan.
            // Misalnya, jika ada kolom 'sent_to_deskprint_id' di tabel orders:
            // $isInUse = \App\Models\PhotobookOrder::where('sent_to_deskprint_id', $deskprint->id)->exists();
            // if ($isInUse) {
            //     return response()->json(['error' => 'Cannot delete deskprint because it is associated with orders.'], 400);
            // }

            $deskprint->delete();

            return response()->json(['message' => 'Deskprint deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Failed to delete deskprint ID ' . $deskprint->id . ': ' . $e->getMessage());
            
            // Tangani error khusus jika ada constraint
            if ($e->getCode() == 23000) {
                 return response()->json(['error' => 'Cannot delete deskprint because it is associated with data.'], 400);
            }
            
            return response()->json(['error' => 'Failed to delete deskprint. Please try again.'], 500);
        }
    }
}
