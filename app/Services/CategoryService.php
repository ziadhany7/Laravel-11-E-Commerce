<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Intervention\Image\Laravel\Facades\Image;

class CategoryService
{
    public function getAllCategories()
    {
        return Category::orderBy("id", "desc")->paginate(10);
    }
    public function getCategoryById($id)
    {
        return Category::find($id);
    }


    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:categories,slug',
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $category = new Category();
        $category->name = $request->name;
        $category->slug = $request->slug;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = Carbon::now()->timestamp . '.' . $image->extension();
            $this->generateCategoryThumbnailImage($image, $imageName);
            $category->image = $imageName;
        }

        $category->save();
        return $category;
    }

    public function updateCategory(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:categories,slug,' . $id,
            'image' => 'mimes:png,jpg,jpeg|max:2048',
        ]);

        $category = Category::findOrFail($id);
        $category->name = $request->name;
        $category->slug = $request->slug;

        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/categories/') . $category->image)) {
                File::delete(public_path('uploads/categories/') . $category->image);
            }

            $image = $request->file('image');
            $imageName = Carbon::now()->timestamp . '.' . $image->extension();
            $this->generateCategoryThumbnailImage($image, $imageName);
            $category->image = $imageName;
        }

        $category->save();
        return $category;
    }

    public function deleteCategory($category_id)
    {
        $category = Category::findOrFail($category_id);

        if (File::exists(public_path('uploads/categories/') . $category->image)) {
            File::delete(public_path('uploads/categories/') . $category->image);
        }

        $category->delete();
    }

    private function generateCategoryThumbnailImage($image, $imageName)
    {
        $destinationPath = public_path('uploads/categories');
        $img = Image::read($image->path());
        $img->cover(124, 124, 'top')
            ->resize(124, 124, function ($constraint) {
                $constraint->aspectRatio();
            })
            ->save($destinationPath . '/' . $imageName);
    }
}
