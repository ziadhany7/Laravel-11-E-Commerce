<?php

namespace App\Http\Controllers;
use Surfsidemedia\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(){
        $items = Cart::instance('cart')->content();
        return view('cart.cart-home',compact('items'));
    }
    public function add_to_cart(Request $request){
        Cart::instance('cart')->add($request->id, $request->name, $request->quantity, $request->price)->associate('App\Models\Product');
        return redirect()->back();
    }
}
