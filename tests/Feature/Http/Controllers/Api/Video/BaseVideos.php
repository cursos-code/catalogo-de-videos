<?php

namespace Tests\Feature\Http\Controllers\Api\Video;

use App\Models\Video;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\Feature\Http\Controllers\Api\StoreTrait;
use Tests\TestCase;

abstract class BaseVideos extends TestCase
{

    use DatabaseMigrations;

    use StoreTrait;

    /**
     * @var array
     */
    protected $data;

    protected function setUp(): void
    {
        parent::setUp();
        $this->data = [
            'title' => 'new title',
            'description' => 'video description',
            'year_launched' => 2015,
            'opened' => true,
            'rating' => '18',
            'duration' => 60,
        ];
    }

    public function getStruct()
    {
        return [
            'title' => 'required|max:255',
            'description' => 'required',
            'year_launched' => 'required|integer|date_format:Y',
            'opened' => 'boolean',
            'rating' => 'required|in:' . implode(',', Video::RATING_LIST),
            'duration' => 'required|integer',
            'categories_id' => 'required|array|exists:categories,id,deleted_at,NULL',
            'genres_id' => ['required', 'array', 'exists:genres,id,deleted_at,NULL'],
            'video_file' => 'nullable|mimetypes:video/mp4|max:' . Video::MAX_UPLOAD_VIDEO_SIZE,
            'thumb_file' => 'nullable|image|max:' . Video::MAX_UPLOAD_THUMB_SIZE,
            'banner_file' => 'nullable|image|max:' . Video::MAX_UPLOAD_BANNER_SIZE,
            'trailer_file' => 'nullable|mimetypes:image/png|max:' . Video::MAX_UPLOAD_TRAILER_SIZE,
        ];
    }

    protected function getModel()
    {
        return Video::class;
    }

    protected function getStructure(): array
    {
        return [
            'title',
            'description',
            'year_launched',
            'opened',
            'rating',
            'duration',
            'created_at',
            'updated_at',
            'deleted_at'
        ];
    }

    protected function getValidationFields($fields, $model, $data, $sendData = null)
    {
        $output = [];
        foreach ($fields as $field) {
            $output[] = [
                $field => [
                    'route' => route(
                        $model ? 'videos.update' : 'videos.store',
                        $model ? ['video' => $model->id] : []
                    ),
                    'params' => array_merge($data, [$field => $sendData])
                ],
            ];
        }
        return $output;
    }

}
