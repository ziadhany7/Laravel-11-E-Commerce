<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use function PHPUnit\Framework\returnValueMap;

class AdminController extends Controller
{
    public function index()
    {
        return view("admin.index");

        //Purpose of index Method : Shows the main admin page.
    }
    public function brands()
    {
        $brands = Brand::orderBy("id", "desc")->paginate(10);
        return view("admin.brands.brands-home", compact("brands"));

        //Purpose of brand Method:        Displays a paginated list of brands in the admin area.
        //Brand::orderBy("id", "desc"):   Fetches brand records from the database, sorting them by id in descending order.
        //paginate(10):                   Limits the results to 10 per page and provides pagination functionality.

    }
    public function add_brands()
    {
        return view("admin.brands.brands-add");
    }
    public function brands_store(Request $request)
    {
        //Request $request: This parameter represents the HTTP request object. It contains the data submitted through the form (e.g., name, slug, image).
        $request->validate([                         //   Validates the form data to ensure correctness before proceeding.
            'name' => 'required',                           //   Ensures the name field is present and not empty.
            'slug' => 'required|unique:brands,slug',        //   Ensures the slug field is present and unique in the brands table (slug column). {{Note: There is a typo in requierd; it should be required}}.
            'image' => 'mimes:png,jpg,jpeg|max:2048'        //   Ensures the uploaded file is an image with the specified extensions (png, jpg, jpeg).  &   Restricts the file size to a maximum of 2MB (2048 KB).
        ]);

        $brand = new Brand();        // Creates a new instance of the Brand model.
        // Assigns the name and slug fields from the request to the corresponding model properties.
        $brand->slug = $request->slug;
        $brand->name = $request->name;

        $image = $request->file('image');                            // Retrieves the uploaded file (image).
        $file_extention = $image->extension();     // Retrieves the file extension of the uploaded image (e.g., png, jpg).
        $file_name = Carbon::now()->timestamp . '.' . $file_extention;  // Generates a unique timestamp for the file name to avoid name conflicts.  &  {Carbon::now()->timestamp . '.' . $file_extention} Combines the timestamp and file extension to create a unique file name.
        $this->GenerateBrandThumbailsImage($image, $file_name);
        $brand->image = $file_name;                                    // Stores the generated file name in the image column of the Brand model.
        $brand->save();                                               // Saves the Brand model (and its properties) to the database, creating a new record in the brands table.
        return redirect()->route('admin.brands')->with('status', 'brand added successfully!!');
        // redirect()->route('admin.brands'): Redirects the user to the route named admin.brands (assumed to display the brands list).
        // with('status', 'brand added successfully!!'):
        // Passes a flash message (status) with the value 'brand added successfully!!'.  {This message is often displayed to the user as feedback (e.g., in a success alert).}
    }
    // Full [[ brands_store Method ]] Summary:
    // 1- Validate the form inputs.
    // 2- Create a new Brand instance and assign values from the request.
    // 3- Handle the uploaded image (generate a unique name, save it, and assign it to the image property).
    // 4- Save the brand record to the database.
    // 5- Redirect the user back to the brands page with a success message.



    public function brands_edit($brand_id) //This method is used to fetch a specific brand record by its ID and pass it to a view where the user can edit the brand details.
    {
        $brand = Brand::find($brand_id);      //Brand::find($brand_id): Queries the database for a brand record where the primary key matches the provided $brand_id. &  If the brand exists, it retrieves the record as an instance of the Brand model. If not, it returns null.
        //Why this step?: The retrieved record contains all the information about the brand (e.g., name, slug, image) that needs to be displayed and potentially edited.
        return view("admin.brands.brands-edit", compact('brand'));
        // view("admin.brands-edit"): Loads the Blade template named brands-edit located in the resources/views/admin directory.
        // compact('brand'): Passes the $brand variable to the view. In the view, the variable can be accessed directly using $brand.

        // Why this step?:
        // The view file (brands-edit.blade.php) is responsible for rendering the edit form.
        // This form is pre-filled with the details of the brand fetched from the database (e.g., its name, slug, and current image).
    }
    // Full [[ brands_edit Method ]] Summary:
    // 1- Input: Receives the ID of the brand to be edited ($brand_id).
    // 2- Processing: -> 1- Fetches the brand record from the database.  2- Passes the record to the edit view (admin.brands-edit).
    // 3- Output: -> Renders the brands-edit view with the brand's details pre-filled, enabling the user to make changes.


    public function brands_update(Request $request)
    {
        $request->validate([                                           //   Validates the form data to ensure correctness before proceeding.
            'name' => 'required',                                     //   Ensures the name field is present and not empty.
            'slug' => 'required|unique:brands,slug,' . $request->id,   //  The slug field must not be empty. & The slug must be unique in the brands table (except for the current brand being updated, identified by $request->id).
            'image' => 'mimes:png,jpg,jpeg|max:2048',               //   Ensures the uploaded file is an image with the specified extensions (png, jpg, jpeg).  &   Restricts the file size to a maximum of 2MB (2048 KB).
        ]);

        $brand = Brand::find($request->id);

        // Assigns the name and slug fields from the request to the corresponding model properties.
        $brand->slug = $request->slug;
        $brand->name = $request->name;

        if ($request->hasFile('image')) { // Confirms whether the image file exists in the request.
            if (File::exists(public_path('uploads/brands') . '/' . $brand->image)) { // File::exists(...): Checks if the old image exists in the specified directory.
                File::delete(public_path('uploads/brands') . '/' . $brand->image);   // File::delete(...): Deletes the old image to avoid unused files taking up space.
            }
            $image = $request->file('image');           // Retrieves the uploaded file (image).
            $file_extention = $image->extension();     // Retrieves the file extension of the uploaded image (e.g., png, jpg).
            $file_name = Carbon::now()->timestamp . '.' . $file_extention;  // Generates a unique timestamp for the file name to avoid name conflicts.  &  {Carbon::now()->timestamp . '.' . $file_extention} Combines the timestamp and file extension to create a unique file name.
            $this->GenerateBrandThumbailsImage($image, $file_name); // Calls a helper method to generate thumbnail images. (Assumes this method resizes and saves the uploaded image).
            $brand->image = $file_name;  // Stores the generated file name in the image column of the Brand model.
        }

        $brand->save();   // Persists the updated brand object into the database.
        return redirect()->route('admin.brands')->with('status', 'Brand Updated successfully!!!');
        // redirect()->route('admin.brands'): Redirects the user to the route named admin.brands (assumed to display the brands list).
        // with('status', 'Brand Updated successfully!!!'):
        // Passes a flash message (status) with the value 'Brand Updated successfully!!!'.  {This message is often displayed to the user as feedback (e.g., in a success alert).}
    }
    // Full [[ brands_update Method ]] Summary:
    // 1- Validates incoming data to ensure correctness.
    // 2- Retrieves the brand by ID from the database.
    // 3- Updates text fields (name and slug).
    // 4- Processes image upload: 1-Deletes the old image. 2-Saves the new image with a unique name. 3-Updates the image field in the database.
    // 5- Saves the updated brand record.
    // 6- Redirects the user to the brand list page with a success message.

    public function GenerateBrandThumbailsImage($image, $imageName)
    {
        $destinationPath = public_path('uploads/brands'); // This line defines the directory where the thumbnail will be saved.
        $img = Image::read($image->path());        // This line uses the Image facade (likely from the Intervention Image package, a popular image manipulation library for Laravel) to read the image file.  &  $image->path() retrieves the file path of the uploaded image.
        $img->cover(124, 124, 'top');   // The cover() method ensures the image fills the specified dimensions while maintaining its aspect ratio. It also uses the top parameter to crop from the top of the image if it doesn’t fit exactly into the defined dimensions.
        $img->resize(124, 124, function ($constraint) {     // The resize() method ensures that the image is resized to exactly 124x124 while maintaining its aspect ratio.
            $constraint->aspecRatio();              // The $constraint->aspectRatio() ensures that the width and height scale proportionally, so the image doesn't get distorted.
        })->save($destinationPath . '/' . $imageName);  // This saves the processed image to the uploads/brands directory with the provided $imageName as the file name.
    }
    // Full [[ GenerateBrandThumbailsImage Method ]] Summary:
    // 1- This function takes an image.
    // 2- reads it.
    // 3- crops it to a 124x124 pixel box.
    // 4- resizes it while maintaining the aspect ratio.
    // 5- saves the processed image in the specified directory.
    public static function brands_delete($brand_id)
    {
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
            'slug' => 'required|unique:products,slug,'.$request->id,
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
        $product = Product ::find($request->id);
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
            $imageName = $current_timestamp.'.'.$image->extension();
            $this->GenerateProductThumbailsImage($image, $imageName);
            $product->image = $imageName;
        }

        $gallery_arr = array();
        $gallery_images = "";
        $counter = 1;
        if ($request->hasFile('images')) {
            foreach (explode(',', $product->images) as $ofile)
            {
                if (File::exists(public_path('uploads/products').'/'.$ofile)) {
                    File::delete(public_path('uploads/products').'/'.$ofile);
                }
                if (File::exists(public_path('uploads/products/thumbnails').'/'.$ofile)) {
                    File::delete(public_path('uploads/products/thumbnails').'/'.$ofile);
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
}
