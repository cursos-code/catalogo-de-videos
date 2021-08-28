<?php

namespace Tests\Unit\Models;

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
    private $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = new Video();
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
        ];
        $this->assertEquals($fillable, $this->category->getFillable());
        $dates = ['deleted_at', 'created_at', 'updated_at'];
        foreach ($this->category->getDates() as $date) {
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
        $this->assertEqualsCanonicalizing($casts, $this->category->getCasts());
        $incrementing = false;
        $this->assertEquals($incrementing, $this->category->getIncrementing());
    }

}
