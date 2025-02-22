<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ShopService;
use GuzzleHttp\Psr7\Query;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    protected $shopService;

    public function __construct(ShopService $shopService)
    {
        $this->shopService = $shopService;
    }
    public function index(Request $request)
    {
        $data = $this->shopService->getProducts($request);
        return view('shop.shop-home', $data);
    }
    
    public function product_details($product_slug)
    {
        $data = $this->shopService->getProductDetails($product_slug);
        return view('shop.details', $data);
    }
}
