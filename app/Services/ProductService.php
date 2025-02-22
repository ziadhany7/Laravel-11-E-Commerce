<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

use Intervention\Image\Laravel\Facades\Image;

class ProductService
{
    public function getAllProducts()
    {
        return Product::orderBy('created_at', 'DESC')->paginate(10);
    }
    public function getProductById($id)
    {
        return Product::findOrFail($id);
    }

    public function getCategoriesAndBrands()
    {
        return [
            'categories' => \App\Models\Category::select('id', 'name')->orderBy('name')->get(),
            'brands' => \App\Models\Brand::select('id', 'name')->orderBy('name')->get(),
        ];
    }

    public function storeProduct(Request $request)
    {
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

        $product = new Product();
        $this->mapProductData($product, $request);
        $product->save();

        return $product;
    }

    public function updateProduct(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug,' . $id,
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
            'brand_id' => 'required'
        ]);

        $product = Product::findOrFail($id);
        $this->mapProductData($product, $request);
        $product->save();

        return $product;
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);
        $this->deleteImages($product);
        $product->delete();
    }

    private function mapProductData(Product $product, Request $request)
    {
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

        if ($request->hasFile('image')) {
            $this->deleteImage($product->image);
            $product->image = $this->processImage($request->file('image'));
        }

        if ($request->hasFile('images')) {
            $this->deleteGalleryImages($product->images);
            $product->images = $this->processGalleryImages($request->file('images'));
        }
    }

    private function processImage($image)
    {
        $imageName = Carbon::now()->timestamp . '.' . $image->extension();
        $this->resizeAndSaveImage($image, $imageName);
        return $imageName;
    }

    private function processGalleryImages($images)
    {
        $galleryArr = [];
        $counter = 1;
        foreach ($images as $file) {
            $imageName = Carbon::now()->timestamp . '-' . $counter . '.' . $file->extension();
            $this->resizeAndSaveImage($file, $imageName);
            $galleryArr[] = $imageName;
            $counter++;
        }
        return implode(',', $galleryArr);
    }

    private function resizeAndSaveImage($image, $imageName)
    {
        $destinationPath = public_path('uploads/products');
        $thumbnailPath = public_path('uploads/products/thumbnails');

        $img = Image::read($image->path());
        $img->resize(540, 689)->save($destinationPath . '/' . $imageName);
        $img->resize(104, 104)->save($thumbnailPath . '/' . $imageName);
    }

    private function deleteImage($image)
    {
        if ($image) {
            File::delete(public_path('uploads/products/' . $image));
            File::delete(public_path('uploads/products/thumbnails/' . $image));
        }
    }

    private function deleteGalleryImages($images)
    {
        if ($images) {
            foreach (explode(',', $images) as $image) {
                $this->deleteImage($image);
            }
        }
    }

    private function deleteImages($product)
    {
        $this->deleteImage($product->image);
        $this->deleteGalleryImages($product->images);
    }
}
