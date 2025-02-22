<?php

namespace App\Services;

use App\Models\Slide;
use App\Models\Category;
use App\Models\Product;
use App\Models\Contact;
use Illuminate\Http\Request;

class HomeService
{
    public function getHomeData()
    {
        $slides = Slide::where('status', 1)->take(3)->get();
        $categories = Category::orderBy('name')->get();
        $sproducts = Product::whereNotNull('sale_price')->where('sale_price', '<>', '')->inRandomOrder()->take(8)->get();
        $fproducts = Product::where('featured', 1)->take(8)->get();

        return compact('slides', 'categories', 'sproducts', 'fproducts');
    }

    public function storeContact(Request $request)
    {
        $request->validate([
            'name' => 'required|max:100',
            'email' => 'required|email',
            'phone' => 'required|numeric|digits:10',
            'comment' => 'required'
        ]);

        Contact::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'comment' => $request->comment
        ]);

        return redirect()->back()->with('success', 'Your message has been sent successfully');
    }

    public function searchProducts($query)
    {
        return Product::where('name', 'LIKE', "%{$query}%")->take(8)->get();
    }
}
