<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BasicCrudController;
use App\Models\Video;
use App\Rules\GenreHasCategoriesRule;
use Illuminate\Http\Request;

class VideoController extends BasicCrudController
{

    private $customRules = [];

    public function store(Request $request)
    {
        $this->createCustomRules($request);
        $validData = $this->validate($request, $this->getRules());
        $video = $this->getModel()::create($validData);
        $video->refresh();
        return $video;
    }

    public function update(Request $request, $id)
    {
        $this->createCustomRules($request);
        $validData = $this->validate($request, $this->getUpdateRules());
        $video = $this->findOrFail($id);
        $video->update($validData);
        $video->refresh();
        return $video;
    }

    private function createCustomRules(Request $request)
    {
        $categories = $request->get('categories_id');
        $this->customRules['genres_id'][] = new GenreHasCategoriesRule(is_array($categories) ? $categories : []);
    }

    protected function getModel()
    {
        return Video::class;
    }

    protected function getRules()
    {
        return array_merge(
            [
                'title' => 'required|max:255',
                'description' => 'required',
                'year_launched' => 'required|integer|date_format:Y',
                'opened' => 'boolean',
                'rating' => 'required|in:' . implode(',', Video::RATING_LIST),
                'duration' => 'required|integer',
                'categories_id' => 'required|array|exists:categories,id,deleted_at,NULL',
                'genres_id' => ['required', 'array', 'exists:genres,id,deleted_at,NULL'],
                'video_file' => 'nullable|mimetypes:video/mp4|max:'.Video::MAX_UPLOAD_VIDEO_SIZE,
                'thumb_file' => 'nullable|image|max:'.Video::MAX_UPLOAD_THUMB_SIZE,
                'banner_file' => 'nullable|image|max:'.Video::MAX_UPLOAD_BANNER_SIZE,
                'trailer_file' => 'nullable|mimetypes:image/png|max:'.Video::MAX_UPLOAD_TRAILER_SIZE,
            ],
            $this->customRules
        );
    }

    protected function getUpdateRules()
    {
        return $this->getRules();
    }

}
