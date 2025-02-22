<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Slide;
use App\Services\HomeService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected $homeService;

    public function __construct(HomeService $homeService)
    {
        $this->homeService = $homeService;
    }

    public function index()
    {
        $data = $this->homeService->getHomeData();
        return view('index', $data);
    }

    public function contact()
    {
        return view('contact.contact-home');
    }

    public function contact_store(Request $request)
    {
        return $this->homeService->storeContact($request);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $results = $this->homeService->searchProducts($query);
        return response()->json($results);
    }
}
