<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Illuminate\Support\Facades\Session;
use Surfsidemedia\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session as FacadesSession;
use App\Services\CartService;
class CartController extends Controller
{
    protected $cartService;
    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }
    public function index()
    {
        $items = Cart::instance('cart')->content();
        return view('cart.cart-home', compact('items'));
    }
    public function add_to_cart(Request $request)
    {
        $this->cartService->addToCart($request->id, $request->name, $request->quantity, $request->price);
        return redirect()->back();
    }

    public function increase_cart_quantity($rowId)
    {
        $this->cartService->increaseCartQuantity($rowId);
        return redirect()->back();
    }

    public function decrease_cart_quantity($rowId)
    {
        $this->cartService->decreaseCartQuantity($rowId);
        return redirect()->back();
    }

    public function remove_item($rowId)
    {
        $this->cartService->removeItem($rowId);
        return redirect()->back();
    }

    public function empty_cart()
    {
        $this->cartService->emptyCart();
        return redirect()->back();
    }

    public function apply_coupon_code(Request $request)
    {
        $result = $this->cartService->applyCouponCode($request->coupon_code);

        return redirect()->back()->with($result['status'], $result['message']);
    }

    public function remove_coupon_code()
    {
        $this->cartService->removeCouponCode();
        return back()->with('success', 'Coupon has been removed!');
    }

    public function checkout()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $address = Address::where('user_id', Auth::user()->id)->where('isdefault', 1)->first();
        return view('checkout.checkout-home', compact('address'));
    }

    public function place_an_order(Request $request)
    {
        $user_id = Auth::user()->id;
        $address = Address::where('user_id', $user_id)->where('isdefault', true)->first();

        if (!$address) {
            $request->validate([
                'name' => 'required|max:100',
                'phone' => 'required|numeric|digits:10',
                'zip' => 'required|numeric|digits:6',
                'state' => 'required',
                'city' => 'required',
                'address' => 'required',
                'locality' => 'required',
                'landmark' => 'required',
            ]);

            $address = Address::create($request->all() + ['user_id' => $user_id, 'country' => 'Egypt', 'isdefault' => true]);
        }

        $this->cartService->setAmountForCheckout();

        $orderData = array_merge($address->toArray(), Session::get('checkout'));
        $orderData['user_id'] = $user_id;
        $order = Order::make($orderData);

        foreach (Cart::instance('cart')->content() as $item) {
            OrderItem::create(['product_id' => $item->id, 'order_id' => $order->id, 'price' => $item->price, 'quantity' => $item->qty]);
        }

        if ($request->mode == "cod") {
            Transaction::create(['user_id' => $user_id, 'order_id' => $order->id, 'mode' => $request->mode, 'status' => "pending"]);
        }

        $this->cartService->emptyCart();
        Session::put('order_id', $order->id);
        return redirect()->route('cart.order.confirmation');
    }

    public function order_confirmation()
    {
        if (Session::has('order_id')) {
            $order = Order::find(Session::get('order_id'));
            return view('checkout.order-confirmation', compact('order'));
        }
        return redirect()->route('cart.index');
    }
}
