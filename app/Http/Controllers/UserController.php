<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        return view("user.index");
    }

    public function orders()
    {
        $orders = $this->userService->getOrders();
        return view('user.orders.orders-home', compact('orders'));
    }

    public function order_details($order_id)
    {
        $orderData = $this->userService->getOrderDetails($order_id);
        if ($orderData) {
            return view('user.orders.orders-details', $orderData);
        }
        return redirect()->route('login');
    }

    public function order_cancel(Request $request)
    {
        $isCanceled = $this->userService->cancelOrder($request->order_id);
        if ($isCanceled) {
            return back()->with('status', "Order has been cancelled successfully!");
        }
        return back()->with('error', "Order cancellation failed!");
    }
}
