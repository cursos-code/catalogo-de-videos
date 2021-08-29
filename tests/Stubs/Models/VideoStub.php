<?php

namespace Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

class VideoStub extends Model
{
    const MAX_UPLOAD_VIDEO_SIZE = 10;
    const MAX_UPLOAD_THUMB_SIZE = 10;
    const MAX_UPLOAD_BANNER_SIZE = 10;
    const MAX_UPLOAD_TRAILER_SIZE = 10;

    protected $table = 'videos_stub';
    protected $fillable = [
        'title',
        'description',
        'year_launched',
        'opened',
        'rating',
        'duration'
    ];

    public static function createTable()
    {
        \Schema::create('videos_stub', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->text('description');
            $table->smallInteger('year_launched');
            $table->boolean('opened')->default(false);
            $table->string('rating', 3);
            $table->smallInteger('duration');
            $table->timestamps();
        });
    }

    public static function dropTable()
    {
        \Schema::dropIfExists('videos_stub');
    }

}
