<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class UserService
{
    public function getOrders()
    {
        return Order::where('user_id', Auth::user()->id)
            ->orderBy('created_at', 'DESC')
            ->paginate(10);
    }

    public function getOrderDetails($order_id)
    {
        $order = Order::where('user_id', Auth::user()->id)
            ->where('id', $order_id)
            ->first();

        if ($order) {
            $orderItems = OrderItem::where('order_id', $order->id)
                ->orderBy('id')
                ->paginate(12);
            $transaction = Transaction::where('order_id', $order->id)->first();
            return compact('order', 'orderItems', 'transaction');
        }

        return null;
    }

    public function cancelOrder($order_id)
    {
        $order = Order::find($order_id);
        if ($order) {
            $order->status = "canceled";
            $order->canceled_date = Carbon::now();
            $order->save();
            return true;
        }
        return false;
    }
}
