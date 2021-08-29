<?php

namespace Tests\Unit\Models\Video;

use App\Models\Traits\UploadFiles;
use App\Models\Traits\Uuid;
use App\Models\Video;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class VideoTest extends TestCase
{

    /**
     * @var Video
     */
    private $video;

    protected function setUp(): void
    {
        parent::setUp();
        $this->video = new Video();
    }

    public function testIfUseTraits()
    {
        $traits = [
            SoftDeletes::class,
            Uuid::class,
            UploadFiles::class
        ];
        $classTraits = array_keys(class_uses(Video::class));
        $this->assertEqualsCanonicalizing($traits, $classTraits);
    }

    public function testIfPropertiesAreCorrect()
    {
        $fillable = [
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
        $this->assertEquals($fillable, $this->video->getFillable());

        $dates = ['deleted_at', 'created_at', 'updated_at'];
        foreach ($this->video->getDates() as $date) {
            $this->assertContains($date, $dates);
        }

        $casts = [
            'id' => 'string',
            'opened' => 'boolean',
            'year_launched' => 'integer',
            'duration' => 'integer',
            'categories_id' => 'array',
            'genres_id' => 'array',
        ];
        $this->assertEqualsCanonicalizing($casts, $this->video->getCasts());

        $incrementing = false;
        $this->assertEquals($incrementing, $this->video->getIncrementing());

        $fileFields = ['video_file', 'thumb_file', 'banner_file', 'trailer_file'];
        $this->assertEqualsCanonicalizing($fileFields, $this->video::$fileFields);
    }

}
