<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
// Asumsi nama model Coupon, sesuaikan jika berbeda
use App\Models\Coupon; 
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminCouponController extends Controller
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
            'search' => 'nullable|string|max:255', // Untuk pencarian berdasarkan kode
            'is_active' => 'nullable|boolean', // Untuk filter berdasarkan status aktif
        ]);

        $query = Coupon::query(); // Sesuaikan dengan nama model Anda

        // Filter berdasarkan pencarian kode kupon
        if (!empty($validated['search'])) {
            $query->where('code', 'like', '%' . $validated['search'] . '%');
        }

        // Filter berdasarkan status aktif
        if (isset($validated['is_active'])) {
            $query->where('is_active', $validated['is_active']);
        }

        $perPage = $validated['per_page'] ?? 15;
        $coupons = $query->latest()->paginate($perPage);

        return response()->json($coupons);
    }

    /**
     * Store a newly created resource in storage.
     * Digunakan oleh Super Admin untuk membuat kupon baru.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code', // Sesuaikan nama tabel 'coupons'
            'discount_percent' => 'required|numeric|min:0|max:100', // Diskon dalam persentase
            // 'discount_amount' => 'required|numeric|min:0', // Atau jika menggunakan diskon tetap, gunakan ini dan hapus discount_percent
            'max_uses' => 'nullable|integer|min:0', // Maksimal penggunaan total (0 atau null = tak terbatas)
            'max_uses_per_user' => 'nullable|integer|min:1', // Maksimal penggunaan per user
            'starts_at' => 'nullable|date', // Tanggal mulai berlaku
            'expires_at' => 'nullable|date|after:starts_at', // Tanggal kadaluarsa, harus setelah starts_at
            'is_active' => 'sometimes|boolean', // Default akan diambil dari model jika tidak ada
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $coupon = Coupon::create($validated); // Sesuaikan dengan nama model Anda

            return response()->json($coupon, 201);
        } catch (\Exception $e) {
            Log::error('Failed to create coupon: ' . $e->getMessage(), ['validated_data' => $validated]);
            return response()->json(['error' => 'Failed to create coupon. Please try again.'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Coupon  $coupon // Sesuaikan dengan nama model
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Coupon $coupon): JsonResponse // Sesuaikan dengan nama model
    {
        return response()->json($coupon);
    }

    /**
     * Update the specified resource in storage.
     * Digunakan oleh Super Admin untuk mengubah kupon.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Coupon  $coupon // Sesuaikan dengan nama model
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Coupon $coupon): JsonResponse // Sesuaikan dengan nama model
    {
        $validated = $request->validate([
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('coupons', 'code')->ignore($coupon->id), // Sesuaikan nama tabel
            ],
            'discount_percent' => 'sometimes|required|numeric|min:0|max:100',
            // 'discount_amount' => 'sometimes|required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:0',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $coupon->update($validated);

            return response()->json($coupon);
        } catch (\Exception $e) {
            Log::error('Failed to update coupon ID ' . $coupon->id . ': ' . $e->getMessage(), ['validated_data' => $validated]);
            return response()->json(['error' => 'Failed to update coupon. Please try again.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Digunakan oleh Super Admin untuk menghapus kupon.
     *
     * @param  \App\Models\Coupon  $coupon // Sesuaikan dengan nama model
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Coupon $coupon): JsonResponse // Sesuaikan dengan nama model
    {
        try {
            // Pertimbangkan: Apakah kupon yang sudah digunakan boleh dihapus?
            // Mungkin lebih baik di-set 'is_active' = false daripada dihapus secara permanen.
            // Tapi jika dihapus, pastikan tidak ada data penting yang hilang.
            
            $coupon->delete();

            return response()->json(['message' => 'Coupon deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Failed to delete coupon ID ' . $coupon->id . ': ' . $e->getMessage());
            
            // Tangani error khusus jika ada constraint
            if ($e->getCode() == 23000) {
                 return response()->json(['error' => 'Cannot delete coupon because it is associated with orders or usage records.'], 400);
            }
            
            return response()->json(['error' => 'Failed to delete coupon. Please try again.'], 500);
        }
    }
}
