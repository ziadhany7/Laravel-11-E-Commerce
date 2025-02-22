<?php


namespace App\Services;

use App\Models\Slide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Intervention\Image\Laravel\Facades\Image;

class SlideService
{
    public function getAllSlides()
    {
        return Slide::orderBy('id', 'DESC')->paginate(12);
    }
    public function getSlideById($id)
    {
        return Slide::findOrFail($id);
    }
    public function storeSlide(Request $request)
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
        $file_extention = $image->extension();
        $file_name = Carbon::now()->timestamp . '.' . $file_extention;

        $this->generateThumbnail($image, $file_name);
        $slide->image = $file_name;
        $slide->save();
    }

    public function updateSlide(Request $request)
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
        if (!$slide) {
            return false;
        }

        $slide->tagline = $request->tagline;
        $slide->title = $request->title;
        $slide->subtitle = $request->subtitle;
        $slide->link = $request->link;
        $slide->status = $request->status;

        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/slides/' . $slide->image))) {
                File::delete(public_path('uploads/slides/' . $slide->image));
            }

            $image = $request->file('image');
            $file_extention = $image->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extention;

            $this->generateThumbnail($image, $file_name);
            $slide->image = $file_name;
        }

        $slide->save();
        return true;
    }

    public function deleteSlide($id)
    {
        $slide = Slide::find($id);
        if (!$slide) {
            return false;
        }

        if (File::exists(public_path('uploads/slides/' . $slide->image))) {
            File::delete(public_path('uploads/slides/' . $slide->image));
        }

        $slide->delete();
        return true;
    }

    private function generateThumbnail($image, $imageName)
    {
        $destinationPath = public_path('uploads/slides');
        $img = Image::read($image->path());
        $img->cover(400, 690, "top");
        $img->resize(400, 690, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath . '/' . $imageName);
    }
}

