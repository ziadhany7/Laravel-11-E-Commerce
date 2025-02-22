<?php

namespace App\Services;

use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponService
{
    public function getAllCoupons()
    {
        return Coupon::orderBy('expiry_date', 'DESC')->paginate(12);
    }

    public function storeCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'type' => 'required',
            'value' => 'required|numeric',
            'cart_value' => 'required|numeric',
            'expiry_date' => 'required|date',
        ]);

        return Coupon::create($request->only(['code', 'type', 'value', 'cart_value', 'expiry_date']));
    }

    public function getCouponById($id)
    {
        return Coupon::find($id);
    }

    public function updateCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'type' => 'required',
            'value' => 'required|numeric',
            'cart_value' => 'required|numeric',
            'expiry_date' => 'required|date',
        ]);

        $coupon = Coupon::find($request->id);
        if ($coupon) {
            $coupon->update($request->only(['code', 'type', 'value', 'cart_value', 'expiry_date']));
        }

        return $coupon;
    }

    public function deleteCoupon($id)
    {
        $coupon = Coupon::find($id);
        if ($coupon) {
            $coupon->delete();
        }

        return $coupon;
    }
}
