<?php

namespace App\Models;

use App\Models\Traits\UploadFiles;
use App\Models\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{

    use SoftDeletes, Uuid, UploadFiles;

    public static $fileFields = ['video_file', 'thumb_file', 'banner_file', 'trailer_file'];

    const RATING_LIST = ['L', '10', '14', '16', '18'];

    const MAX_UPLOAD_VIDEO_SIZE = 50 * 1024 * 1024;
    const MAX_UPLOAD_THUMB_SIZE = 5 * 1024;
    const MAX_UPLOAD_BANNER_SIZE = 10 * 1024;
    const MAX_UPLOAD_TRAILER_SIZE = 1 * 1024 * 1024;

    protected $fillable = [
        'title',
        'description',
        'year_launched',
        'opened',
        'rating',
        'duration',
        'video_file',
        'thumb_file',
        'banner_file',
        'trailer_file'
    ];
    protected $dates = ['deleted_at'];
    protected $casts = [
        'id' => 'string',
        'opened' => 'boolean',
        'year_launched' => 'integer',
        'duration' => 'integer',
        'categories_id' => 'array',
        'genres_id' => 'array',
    ];
    public $incrementing = false;

    public static function create(array $attributes = [])
    {
        $files = self::extractFiles($attributes);
        try {
            \DB::beginTransaction();
            /** @var Video $obj */
            $obj = static::query()->create($attributes);
            static::handleRelations($obj, $attributes);
            $obj->uploadFiles($files);
            \DB::commit();
            return $obj;
        } catch (\Exception $e) {
            if (isset($obj)) {
                $obj->deleteFiles($files);
            }
            \DB::rollBack();
            throw $e;
        }
    }

    public function update(array $attributes = [], array $options = [])
    {
        $files = self::extractFiles($attributes);
        try {
            \DB::beginTransaction();
            $saved = parent::update($attributes, $options);
            static::handleRelations($this, $attributes);
            if ($saved) {
                $this->uploadFiles($files);
            }
            \DB::commit();
            if ($saved && count($files)) {
                $this->deleteOldFiles();
            }
            return $saved;
        } catch (\Exception $e) {
            $this->deleteFiles($files);
            \DB::rollBack();
            throw $e;
        }
    }

    public static function handleRelations(Video $video, array $request)
    {
        if (isset($request['categories_id'])) {
            $video->categories()->sync($request['categories_id']);
        }
        if (isset($request['genres_id'])) {
            $video->genres()->sync($request['genres_id']);
        }
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'videos_categories')->withTrashed();
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'videos_genres')->withTrashed();
    }

    protected function uploadDir()
    {
        return $this->id;
    }
}
