<?php

namespace App\Services;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Intervention\Image\Laravel\Facades\Image;

class BrandService
{
    public function getAllBrands()
    {
        return Brand::orderBy("id", "desc")->paginate(10);
    }

    public function storeBrand(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug',
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $brand = new Brand();
        $brand->slug = $request->slug;
        $brand->name = $request->name;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $file_extention = $image->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extention;
            $this->generateBrandThumbnail($image, $file_name);
            $brand->image = $file_name;
        }

        $brand->save();
        return $brand;
    }

    public function getBrandById($brand_id)
    {
        return Brand::findOrFail($brand_id);
    }

    public function updateBrand(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug,' . $request->id,
            'image' => 'mimes:png,jpg,jpeg|max:2048',
        ]);

        $brand = Brand::findOrFail($request->id);
        $brand->slug = $request->slug;
        $brand->name = $request->name;

        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/brands') . '/' . $brand->image)) {
                File::delete(public_path('uploads/brands') . '/' . $brand->image);
            }
            $image = $request->file('image');
            $file_extention = $image->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extention;
            $this->generateBrandThumbnail($image, $file_name);
            $brand->image = $file_name;
        }

        $brand->save();
        return $brand;
    }

    private function generateBrandThumbnail($image, $imageName)
    {
        $destinationPath = public_path('uploads/brands');
        $img = Image::read($image->path());
        $img->cover(124, 124, 'top');
        $img->save($destinationPath . '/' . $imageName);
    }
}
