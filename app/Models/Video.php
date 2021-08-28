<?php

namespace App\Models;

use App\Models\Traits\UploadFiles;
use App\Models\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{

    use SoftDeletes, Uuid, UploadFiles;

    public static $fileFields = ['video_file'];

    const RATING_LIST = ['L', '10', '14', '16', '18'];

    const MAX_UPLOAD_SIZE = 100;

    protected $fillable = [
        'title',
        'description',
        'year_launched',
        'opened',
        'rating',
        'duration',
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
            // TODO: upload dos arquivos
            \DB::commit();
            return $obj;
        } catch (\Exception $e) {
            if (isset($obj)) {
                //TODO: excluir arquivos uploadados
            }
            \DB::rollBack();
            throw $e;
        }
    }

    public function update(array $attributes = [], array $options = [])
    {
        try {
            \DB::beginTransaction();
            $saved = parent::update($attributes, $options);
            static::handleRelations($this, $attributes);
            if (isset($saved)) {
                // TODO: upload dos arquivos
                // excluir os antigos
            }
            \DB::commit();
            return $saved;
        } catch (\Exception $e) {
            //TODO: excluir arquivos uploadados
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
