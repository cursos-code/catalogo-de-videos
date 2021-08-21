<?php

namespace Tests\Feature\Models;

use App\Models\Video;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class VideoTest extends TestCase
{
    use DatabaseMigrations;

    public function testVideoFields()
    {
        factory(Video::class, 1)->create();
        $videos = Video::all();
        $this->assertCount(1, $videos);
        $videosKeys = array_keys($videos->first()->getAttributes());
        $this->assertEqualsCanonicalizing(
            [
                'id',
                'title',
                'description',
                'year_launched',
                'opened',
                'rating',
                'duration',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            $videosKeys
        );
    }

    public function testCreatingNewVideo()
    {
        /** @var Video $video */
        $video = Video::create([
                                   'title' => 'video title',
                                   'description' => 'video description',
                                   'year_launched' => 2020,
                                   'opened' => false,
                                   'rating' => 'L',
                                   'duration' => 60
                               ]);
        $video->refresh();
        $this->assertTrue(Uuid::isValid($video->id));
        $this->assertEquals($video->title, 'video title');
        $this->assertEquals($video->description, 'video description');
        $this->assertEquals($video->year_launched, 2020);
        $this->assertEquals($video->rating, 'L');
        $this->assertEquals($video->duration, 60);
    }

    public function testEditingVideo()
    {
        /** @var Video $video */
        $video = factory(Video::class)->create(
            ['description' => 'video description']
        )->first();
        $data = [
            'title' => 'video title update',
            'description' => 'video description update',
            'year_launched' => 2020,
            'opened' => false,
            'rating' => 'L',
            'duration' => 60
        ];
        $video->update($data);

        $this->assertTrue(Uuid::isValid($video->id));
        foreach ($data as $key => $value) {
            $this->assertEquals($value, $video->{$key});
        }
    }

    public function testExcludingVideo()
    {
        $videos = factory(Video::class, 2)->create(
            ['rating' => '16']
        );
        $this->assertCount(2, $videos);
        /** @var Video $video */
        $video = $videos->first();
        $video->delete();
        $this->assertCount(2, Video::withTrashed()->get());
        $this->assertCount(1, Video::all());
        $video->restore();
        $this->assertCount(2, Video::all());
    }
}
