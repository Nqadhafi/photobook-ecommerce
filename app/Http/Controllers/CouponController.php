<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\Coupon;

class CouponController extends Controller
{
    /**
     * Validate a coupon code.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'coupon_code' => ['required', 'string', 'exists:coupons,code'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Coupon code is required.'], 422);
        }

        $couponCode = $request->input('coupon_code');
        $coupon = Coupon::active()->valid()->where('code', $couponCode)->first();

        if (!$coupon) {
            return response()->json(['error' => 'Invalid, expired, or inactive coupon code.'], 400);
        }

        if (!$coupon->isUsable()) {
            return response()->json(['error' => 'This coupon is no longer available or has reached its usage limit.'], 400);
        }

        // Validasi tambahan: Cek penggunaan per user
        if ($coupon->max_uses_per_user > 0) {
            $userCouponUsageCount = $user->photobookOrders()->whereHas('coupons', function($query) use ($coupon) {
                $query->where('coupon_id', $coupon->id);
            })->count();

            if ($userCouponUsageCount >= $coupon->max_uses_per_user) {
                return response()->json(['error' => 'You have reached the maximum usage limit for this coupon.'], 400);
            }
        }

        // Hitung subtotal dari cart user untuk estimasi diskon
        $cartItems = $user->photobookCarts()->get();
        $subTotalAmount = 0;
        foreach ($cartItems as $item) {
             if ($item->product && isset($item->product->price)) {
                 $subTotalAmount += $item->quantity * $item->product->price;
             }
        }

        $discountAmount = 0;
        if ($coupon->discount_percent !== null) {
            $discountAmount = ($coupon->discount_percent / 100) * $subTotalAmount;
        }

        return response()->json([
            'message' => 'Coupon is valid.',
            'coupon' => [
                'code' => $coupon->code,
                'discount_percent' => $coupon->discount_percent,
                // 'discount_amount' => $coupon->discount_amount, // Jika menggunakan nominal
                'discount_value' => $discountAmount, // Estimasi diskon
                'description' => $coupon->description,
            ]
        ]);
    }
}