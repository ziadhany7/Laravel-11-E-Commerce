<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Models\Product;
use App\Models\Slide;
use App\Services\BrandService;
use Carbon\Carbon;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use function PHPUnit\Framework\returnValueMap;

class AdminController extends Controller
{
    protected $brandService;

    public function __construct(BrandService $brandService)
    {
        $this->brandService = $brandService;
    }

    public function index()
    {
        $orders = Order::orderBy('created_at', 'DESC')->get()->take(5);
        $dashboardDatas = DB::select("Select sum(total) As TotalAmount,
                                        sum(if(status='ordered',total,0)) As TotalOrderedAmount,
                                        sum(if(status='delivered',total,0)) As TotalDeliveredAmount,
                                        sum(if(status='canceled',total,0)) As TotalCanceledAmount,
                                        Count(*) As Total,
                                        sum(if(status='ordered',1,0)) As TotalOrdered,
                                        sum(if(status='delivered',1,0)) As TotalDelivered,
                                        sum(if(status='canceled',1,0)) As TotalCanceled
                                        From Orders
                                    ");
        $monthlyDatas = DB::select("SELECT M.id As MonthNo, M.name As MonthName,
                                    IFNULL(D.TotalAmount,0) As TotalAmount,
                                    IFNULL(D.TotalOrderedAmount,0) As TotalOrderedAmount,
                                    IFNULL(D.TotalDeliveredAmount,0) As TotalDeliveredAmount,
                                    IFNULL(D.TotalCanceledAmount,0) As TotalCanceledAmount FROM month_names M
                                    LEFT JOIN (Select DATE_FORMAT(created_at, '%b') As MonthName,
                                    MONTH(created_at) As MonthNo,
                                    sum(total) As TotalAmount,
                                    sum(if(status='ordered',total,0)) As TotalOrderedAmount,
                                    sum(if(status='delivered',total,0)) As TotalDeliveredAmount,
                                    sum(if(status='canceled',total,0)) As TotalCanceledAmount
                                    From Orders WHERE YEAR(created_at)=YEAR(NOW()) GROUP BY YEAR(created_at), MONTH(created_at) , DATE_FORMAT(created_at, '%b')
                                    Order By MONTH(created_at)) D On D.MonthNo=M.id");

        $AmountM = implode(',', collect($monthlyDatas)->pluck('TotalAmount')->toArray());
        $OrderedAmountM = implode(',', collect($monthlyDatas)->pluck('TotalOrderedAmount')->toArray());
        $DeliveredAmountM = implode(',', collect($monthlyDatas)->pluck('TotalDeliveredAmount')->toArray());
        $CanceledAmountM = implode(',', collect($monthlyDatas)->pluck('TotalCanceledAmount')->toArray());

        $TotalAmount = collect($monthlyDatas)->sum('TotalAmount');
        $TotalOrderedAmount = collect($monthlyDatas)->sum('TotalOrderedAmount');
        $TotalDeliveredAmount = collect($monthlyDatas)->sum('TotalDeliveredAmount');
        $TotalCanceledAmount = collect($monthlyDatas)->sum('TotalCanceledAmount');


        return view("admin.index", compact('orders', 'dashboardDatas', 'AmountM', 'OrderedAmountM', 'DeliveredAmountM', 'CanceledAmountM', 'TotalAmount', 'TotalOrderedAmount', 'TotalDeliveredAmount', 'TotalCanceledAmount'));

        //Purpose of index Method : Shows the main admin page.
    }
    public function brands()
    {
        $brands = $this->brandService->getAllBrands();
        return view("admin.brands.brands-home", compact("brands"));
    }
    public function add_brands()
    {
        return view("admin.brands.brands-add");
    }
    public function brands_store(Request $request)
    {
        $this->brandService->storeBrand($request);
        return redirect()->route('admin.brands')->with('status', 'Brand added successfully!');
    }

    public function brands_edit($brand_id)
    {
        $brand = $this->brandService->getBrandById($brand_id);
        return view("admin.brands.brands-edit", compact('brand'));
    }

    public function brands_update(Request $request)
    {
        $this->brandService->updateBrand($request);
        return redirect()->route('admin.brands')->with('status', 'Brand updated successfully!');
    }
    public static function brands_delete($brand_id){
        $brand = Brand::find($brand_id);
        if (File::exists(public_path('uploads/brands') . '/' . $brand->image))   // File::exists(...): Checks if the old image exists in the specified directory.
        {
            File::delete(public_path('uploads/brands') . '/' . $brand->image);   // File::delete(...): Deletes the old image to avoid unused files taking up space.
        }
        $brand->delete();
        return redirect()->route('admin.brands')->with('status', 'Brand has been Deleted successfully!');
    }

    public function categories()
    {
        $categories = Category::orderBy("id", "desc")->paginate(10);
        return view("admin.categories.categories-home", compact('categories'));
    }
    public function category_add()
    {
        return view("admin.categories.category-add");
    }
    public function category_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:categories,slug',
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);
        # Creation
        $category = new Category();
        $category->name = $request->name;
        $category->slug = $request->slug;

        $image = $request->file('image');
        $image_extention = $image->extension();
        $image_name = Carbon::now()->timestamp . '.' . $image_extention;
        $this->GenerateCategoryThumbailsImage($image, $image_name);
        $category->image = $image_name;
        $category->save();
        return redirect()->route('admin.categories')->with('status', 'Category added successfully');
    }

    public function GenerateCategoryThumbailsImage($image, $imageName)
    {
        $destinationPath = public_path('uploads/categories');
        $img = Image::read($image->path());
        $img->cover(124, 124, 'top');
        $img->resize(124, 124, function ($constraint) {
            $constraint->aspecRatio();
        })->save($destinationPath . '/' . $imageName);
    }
    public function category_edit($id)
    {
        $category = Category::find($id);
        return view("admin.categories.category-edit", compact('category'));
    }
    public function category_update(Request $request)
    {
        $request->validate([                                           //   Validates the form data to ensure correctness before proceeding.
            'name' => 'required',                                     //   Ensures the name field is present and not empty.
            'slug' => 'required|unique:categories,slug,' . $request->id,   //  The slug field must not be empty. & The slug must be unique in the categories table (except for the current category being updated, identified by $request->id).
            'image' => 'mimes:png,jpg,jpeg|max:2048',               //   Ensures the uploaded file is an image with the specified extensions (png, jpg, jpeg).  &   Restricts the file size to a maximum of 2MB (2048 KB).
        ]);

        $category = Category::find($request->id);

        // Assigns the name and slug fields from the request to the corresponding model properties.
        $category->slug = $request->slug;
        $category->name = $request->name;

        if ($request->hasFile('image')) { // Confirms whether the image file exists in the request.
            if (File::exists(public_path('uploads/categories') . '/' . $category->image))   // File::exists(...): Checks if the old image exists in the specified directory.
            {
                File::delete(public_path('uploads/categories') . '/' . $category->image);   // File::delete(...): Deletes the old image to avoid unused files taking up space.
            }
            $image = $request->file('image');           // Retrieves the uploaded file (image).
            $file_extention = $image->extension();     // Retrieves the file extension of the uploaded image (e.g., png, jpg).
            $file_name = Carbon::now()->timestamp . '.' . $file_extention;  // Generates a unique timestamp for the file name to avoid name conflicts.  &  {Carbon::now()->timestamp . '.' . $file_extention} Combines the timestamp and file extension to create a unique file name.
            $this->GenerateCategoryThumbailsImage($image, $file_name); // Calls a helper method to generate thumbnail images. (Assumes this method resizes and saves the uploaded image).
            $category->image = $file_name;  // Stores the generated file name in the image column of the category model.
        }
        $category->save();   // Persists the updated brand object into the database.
        return redirect()->route('admin.categories')->with('status', 'Category Updated successfully!!!');
        // redirect()->route('admin.categories'): Redirects the user to the route named admin.categories (assumed to display the categories list).
        // with('status', 'Category Updated successfully!!!'):
        // Passes a flash message (status) with the value 'Category Updated successfully!!!'.  {This message is often displayed to the user as feedback (e.g., in a success alert).}
    }
    public function category_delete($category_id)
    {
        $category = Category::find($category_id);
        if (File::exists(public_path('uploads/categories') . '/' . $category->name)) {
            File::delete(public_path('uploads/categories') . '/' . $category->name);
        }
        $category->delete();
        return redirect()->route('admin.categories')->with('status', 'Category Deleted successfully');
    }

    public function products()
    {
        $products = Product::orderBy('created_at', 'DESC')->paginate(10);
        return view("admin.products.products-home", compact('products'));
    }
    public function product_add()
    {
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();
        return view('admin.products.products-add', compact('categories', 'brands'));
    }
    public function product_store(Request $request)
    {
        # Validation
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug',
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required',
            'sale_price' => 'required',
            'SKU' => 'required',
            'stock_status' => 'required',
            'featured' => 'required',
            'quantity' => 'required',
            'image' => 'required|mimes:png,jpg,jpeg|max:2048',
            'category_id' => 'required',
            'brand_id' => 'required'
        ]);

        # Creation
        $product = new Product();
        $product->name = $request->name;
        $product->slug = Str::slug($request->name);
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;

        $current_timestamp = Carbon::now()->timestamp;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = $current_timestamp . '.' . $image->extension();
            $this->GenerateProductThumbailsImage($image, $imageName);
            $product->image = $imageName;
        }
        $gallery_arr = array();
        $gallery_images = "";
        $counter = 1;
        if ($request->hasFile('images')) {
            $allowedFileExtension = ['jpg', 'png', 'jpeg'];
            $files = $request->file('images');
            foreach ($files as $file) {
                $gallery_extension = $file->getClientOriginalExtension();
                $gallery_check = in_array($gallery_extension, $allowedFileExtension);
                if ($gallery_check) {
                    $galleryFileName = $current_timestamp . "-" . $counter . "." . $gallery_extension;
                    $this->GenerateProductThumbailsImage($file, $galleryFileName);
                    array_push($gallery_arr, $galleryFileName);
                    $counter = $counter + 1;
                }
            }
            $gallery_images = implode(',', $gallery_arr);
        }
        $product->images = $gallery_images;
        $product->save();
        return redirect()->route('admin.products')->with('status', 'product has been added successfully');
    }
    public function GenerateProductThumbailsImage($image, $imageName)
    {
        $destinationPathThumbnails = public_path('uploads/products/thumbnails');
        $destinationPath = public_path('uploads/products');
        $img = Image::read($image->path());

        $img->cover(540, 689, "top");
        $img->resize(540, 689, function ($constraint) {
            $constraint->aspecRatio();
        })->save($destinationPath . '/' . $imageName);

        $img->resize(104, 104, function ($constraint) {
            $constraint->aspecRatio();
        })->save($destinationPathThumbnails . '/' . $imageName);
    }
    public function product_edit($id)
    {
        $product = Product::find($id);
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();
        return view("admin.products.product-edit", compact('product', 'categories', 'brands'));
    }
    public function product_update(Request $request)
    {
        # Validation
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug,' . $request->id,
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required',
            'sale_price' => 'required',
            'SKU' => 'required',
            'stock_status' => 'required',
            'featured' => 'required',
            'quantity' => 'required',
            'image' => 'mimes:png,jpg,jpeg|max:2048',
            'category_id' => 'required',
            'brand_id' => 'required',
        ]);
        # Creation
        $product = Product::find($request->id);
        $product->name = $request->name;
        $product->slug = Str::slug($request->name);
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;
        $current_timestamp = Carbon::now()->timestamp;
        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/products') . '/' . $product->image)) {
                File::delete(public_path('uploads/products') . '/' . $product->image);
            }
            if (File::exists(public_path('uploads/products/thumbnails') . '/' . $product->image)) {
                File::delete(public_path('uploads/products/thumbnails') . '/' . $product->image);
            }
            $image = $request->file('image');
            $imageName = $current_timestamp . '.' . $image->extension();
            $this->GenerateProductThumbailsImage($image, $imageName);
            $product->image = $imageName;
        }

        $gallery_arr = array();
        $gallery_images = "";
        $counter = 1;
        if ($request->hasFile('images')) {
            foreach (explode(',', $product->images) as $ofile) {
                if (File::exists(public_path('uploads/products') . '/' . $ofile)) {
                    File::delete(public_path('uploads/products') . '/' . $ofile);
                }
                if (File::exists(public_path('uploads/products/thumbnails') . '/' . $ofile)) {
                    File::delete(public_path('uploads/products/thumbnails') . '/' . $ofile);
                }
            }
            $allowedFileExtension = ['jpg', 'png', 'jpeg'];
            $files = $request->file('images');
            foreach ($files as $file) {
                $gallery_extension = $file->getClientOriginalExtension();
                $gallery_check = in_array($gallery_extension, $allowedFileExtension);
                if ($gallery_check) {
                    $galleryFileName = $current_timestamp . "-" . $counter . "." . $gallery_extension;
                    $this->GenerateProductThumbailsImage($file, $galleryFileName);
                    array_push($gallery_arr, $galleryFileName);
                    $counter = $counter + 1;
                }
            }
            $gallery_images = implode(',', $gallery_arr);
            $product->images = $gallery_images;
        }
        $product->save();
        return redirect()->route('admin.products')->with('status', 'product has been Updated successfully');
    }
    public function product_delete($id)
    {
        $product = Product::find($id);
        if (File::exists(public_path('uploads/products') . '/' . $product->image)) {
            File::delete(public_path('uploads/products') . '/' . $product->image);
        }
        if (File::exists(public_path('uploads/products/thumbnails') . '/' . $product->image)) {
            File::delete(public_path('uploads/products/thumbnails') . '/' . $product->image);
        }
        foreach (explode(',', $product->images) as $ofile) {
            if (File::exists(public_path('uploads/products') . '/' . $ofile)) {
                File::delete(public_path('uploads/products') . '/' . $ofile);
            }
            if (File::exists(public_path('uploads/products/thumbnails') . '/' . $ofile)) {
                File::delete(public_path('uploads/products/thumbnails') . '/' . $ofile);
            }
        }
        $product->delete();
        return redirect()->route("admin.products")->with('status', 'Product has been deleted successfully');
    }
    public function coupons()
    {
        $coupons = Coupon::orderBy('expiry_date', 'DESC')->paginate(12);
        return view('admin.coupons.coupons-home', compact('coupons'));
    }
    public function coupon_add()
    {
        return view('admin.coupons.coupon-add');
    }
    public function coupon_store(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'type' => 'required',
            'value' => 'required|numeric',
            'cart_value' => 'required|numeric',
            'expiry_date' => 'required|date',
        ]);
        $coupon = new Coupon;
        $coupon->code = $request->code;
        $coupon->value = $request->value;
        $coupon->cart_value = $request->cart_value;
        $coupon->expiry_date = $request->expiry_date;
        $coupon->save();
        return redirect()->route('admin.coupons')->with('status', 'Coupon has been added successfully!');
    }
    public function coupon_edit($id)
    {
        $coupon = Coupon::find($id);
        return view('admin.coupons.coupon-edit', compact('coupon'));
    }
    public function coupon_update(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'type' => 'required',
            'value' => 'required|numeric',
            'cart_value' => 'required|numeric',
            'expiry_date' => 'required|date',
        ]);
        $coupon = Coupon::find($request->id);
        $coupon->code = $request->code;
        $coupon->value = $request->value;
        $coupon->cart_value = $request->cart_value;
        $coupon->expiry_date = $request->expiry_date;
        $coupon->save();
        return redirect()->route('admin.coupons')->with('status', 'Coupon has been updated successfully!');
    }
    public function coupon_delete($id)
    {
        $coupon = Coupon::find($id);
        $coupon->delete();

        return redirect()->route('admin.coupons')->with('status', 'Coupon has been Deleted successfully!');
    }
    public function orders()
    {
        $orders = Order::orderBy('created_at', 'DESC')->paginate(12);

        return view('admin.orders.orders-home', compact('orders'));
    }
    public function order_detailsk($order_id)
    {
        $order = Order::find($order_id);
        $orderItems = OrderItem::where('order_id', $order_id)->orderBy('id')->paginate(12);
        $transaction = Transaction::where('order_id', $order_id)->first();
        return view('admin.orders.order-details', compact('order', 'orderItems', 'transaction'));
    }
    public function update_order_status(Request $request)
    {
        $order = Order::find($request->order_id);
        $order->status = $request->order_status;
        if ($request->order_status == 'delivered') {
            $order->delivered_date = Carbon::now();
        } else if ($request->order_status == 'canceled') {
            $order->canceled_date = Carbon::now();
        }
        $order->save();

        if ($request->order_status == "delivered") {
            $transaction = Transaction::where('order_id', $request->order_id)->first();
            $transaction->status = 'approved';
            $transaction->save();
        }
        return back()->with("status", "Status changed successfully!");
    }
    public function slides()
    {
        $slides = Slide::orderBy('id', 'DESC')->paginate(12);
        return view('admin.slides.slides-home', compact('slides'));
    }
    public function slide_add()
    {
        return view('admin.slides.slide-add');
    }
    public function slide_store(Request $request)
    {
        $request->validate([
            'tagline' => 'required',
            'title' => 'required',
            'subtitle' => 'required',
            'link' => 'required',
            'status' => 'required',
            'image' => 'required|mimes:png,jpg,jpeg|max:2048'
        ]);

        $slide = new Slide();
        $slide->tagline = $request->tagline;
        $slide->title = $request->title;
        $slide->subtitle = $request->subtitle;
        $slide->link = $request->link;
        $slide->status = $request->status;
        $image = $request->file('image');

        $file_extention = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp . '.' . $file_extention;
        $this->GenerateslideThumbailsImage($image, $file_name);
        $slide->image = $file_name;
        $slide->save();
        return redirect()->route('admin.slides')->with("status", "slide added successfully!");
    }
    public function GenerateslideThumbailsImage($image, $imageName)
    {
        $destinationPath = public_path('uploads/slides');
        $img = Image::read($image->path());
        $img->cover(400, 690, "top");
        $img->resize(400, 690, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath . '/' . $imageName);
    }

    public function slide_edit($id)
    {
        $slide = Slide::find($id);
        return view('admin.slides.slide-edit', compact('slide'));
    }
    public function slide_update(Request $request)
    {
        $request->validate([
            'tagline' => 'required',
            'title' => 'required',
            'subtitle' => 'required',
            'link' => 'required',
            'status' => 'required',
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $slide = Slide::find($request->id);
        $slide->tagline = $request->tagline;
        $slide->title = $request->title;
        $slide->subtitle = $request->subtitle;
        $slide->link = $request->link;
        $slide->status = $request->status;

        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/slides') . '/' . $slide->image)) {
                File::delete(public_path('uploads/slides') . '/' . $slide->image);
            }
            $image = $request->file('image');
            $file_extention = $request->file('image')->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extention;
            $this->GenerateslideThumbailsImage($image, $file_name);
            $slide->image = $file_name;
        }
        $slide->save();
        return redirect()->route('admin.slides')->with("status", "slide updated successfully!");
    }

    public function slide_delete($id)
    {
        $slide = Slide::find($id);
        if (File::exists(public_path('uploads/slides') . '/' . $slide->image)) {
            File::delete(public_path('uploads/slides') . '/' . $slide->image);
        }
        $slide->delete();
        return redirect()->route('admin.slides')->with("status", "slide deleted successfully!");
    }
    public function contacts()
    {
        $contacts = Contact::orderBy('created_at', 'DESC')->paginate(10);
        return view('admin.contact.contact-home', compact('contacts'));
    }
    public function contact_delete($id)
    {
        $contact = Contact::find($id);
        $contact->delete();
        return redirect()->route('admin.contacts')->with("status", "Contact deleted successfully!");
    }
    public function search(Request $request)
    {
        $query = $request->input('query');
        $results = Product::where('name', 'LIKE', "%{$query}%")->take(8)->get();
        return response()->json($results);
    }

}
