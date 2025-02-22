<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Carbon\Carbon;

class OrderService
{
    public function getAllOrders()
    {
        return Order::orderBy('created_at', 'DESC')->paginate(12);
    }

    public function getOrderDetails($order_id)
    {
        $order = Order::find($order_id);
        $orderItems = OrderItem::where('order_id', $order_id)->orderBy('id')->paginate(12);
        $transaction = Transaction::where('order_id', $order_id)->first();

        return compact('order', 'orderItems', 'transaction');
    }

    public function updateOrderStatus($order_id, $order_status)
    {
        $order = Order::find($order_id);
        $order->status = $order_status;

        if ($order_status == 'delivered') {
            $order->delivered_date = Carbon::now();
        } elseif ($order_status == 'canceled') {
            $order->canceled_date = Carbon::now();
        }
        $order->save();

        if ($order_status == "delivered") {
            $transaction = Transaction::where('order_id', $order_id)->first();
            $transaction->status = 'approved';
            $transaction->save();
        }

        return "Status changed successfully!";
    }
}
