<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\Genre;
use App\Models\Video;
use Illuminate\Database\QueryException;
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
                'video_file'
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

    public function testRollbackOnStore()
    {
        $data = [
            'title' => 'new title',
            'description' => 'video description',
            'year_launched' => 2015,
            'opened' => true,
            'rating' => '18',
            'duration' => 60,
            'categories_id' => [1],
            'genres_id' => [1]
        ];
        try {
            Video::create($data);
        } catch (QueryException $e) {
            self::assertCount(0, Video::all());
        }
    }

    public function testRollbackOnUpdate()
    {
        $data = [
            'title' => 'title',
            'description' => 'video description',
            'year_launched' => 2015,
            'opened' => true,
            'rating' => '18',
            'duration' => 60,
        ];
        $video = Video::create($data);
        $title = $video->title;
        try {
            $video->update(
                array_merge($data, [
                    'title' => 'new title',
                    'categories_id' => [1],
                    'genres_id' => [1]
                ])
            );
        } catch (QueryException $e) {
            $this->assertDatabaseHas('videos', ['title' => $title]);
        }
    }

    public function testHandleRelations()
    {
        $video = factory(Video::class)->create();
        $video::handleRelations($video, []);
        $this->assertCount(0, $video->categories);
        $this->assertCount(0, $video->genres);

        $category = factory(Category::class)->create();
        $video::handleRelations($video, [
            'categories_id' => [$category->id]
        ]);
        $video->refresh();
        $this->assertCount(1, $video->categories);

        $genre = factory(Genre::class)->create();
        $video::handleRelations($video, [
            'genres_id' => [$genre->id]
        ]);
        $video->refresh();
        $this->assertCount(1, $video->genres);

        $video->categories()->delete();
        $video->genres()->delete();
        $video::handleRelations($video, [
            'categories_id' => [$category->id],
            'genres_id' => [$genre->id]
        ]);
        $this->assertCount(1, $video->categories);
        $this->assertCount(1, $video->genres);
    }

    public function testCategoriesSync()
    {
        $categories = factory(Category::class, 3)->create()->pluck('id')->toArray();
        $genre = factory(Genre::class)->create();
        $genre->categories()->sync([$categories[0]]);
        $data = [
            'title' => 'video title',
            'description' => 'video description',
            'year_launched' => 2020,
            'opened' => false,
            'rating' => 'L',
            'duration' => 60,
            'categories_id' => [$categories[0]],
            'genres_id' => [$genre->id]
        ];
        $video = Video::create($data);
        $this->assertDatabaseHas('videos_categories', [
            'category_id' => $categories[0],
            'video_id' => $video->id
        ]);

        $genre->categories()->sync([$categories[1], $categories[2]]);
        $video->update(
            array_merge($data, [
                'categories_id' => [$categories[1], $categories[2]]
            ])
        );
        $this->assertDatabaseMissing('videos_categories', [
            'category_id' => $categories[0],
            'video_id' => $video->id
        ]);
        $this->assertDatabaseHas('videos_categories', [
            'category_id' => $categories[1],
            'video_id' => $video->id
        ]);
        $this->assertDatabaseHas('videos_categories', [
            'category_id' => $categories[2],
            'video_id' => $video->id
        ]);
    }

    public function testGenresSync()
    {
        $category = factory(Category::class)->create();
        $genres = factory(Genre::class, 3)->create();
        foreach ($genres as &$genre) {
            $genre->categories()->sync([$category->id]);
        }
        $data = [
            'title' => 'video title',
            'description' => 'video description',
            'year_launched' => 2020,
            'opened' => false,
            'rating' => 'L',
            'duration' => 60,
            'categories_id' => [$category->id],
            'genres_id' => [$genres[0]->id]
        ];
        $video = Video::create($data);
        $this->assertDatabaseHas('videos_genres', [
            'genre_id' => $genres[0]->id,
            'video_id' => $video->id
        ]);

        $video->update(
            array_merge($data, [
                'genres_id' => [$genres[1]->id, $genres[2]->id]
            ])
        );

        $this->assertDatabaseMissing('videos_genres', [
            'genre_id' => $genres[0]->id,
            'video_id' => $video->id
        ]);
        $this->assertDatabaseHas('videos_genres', [
            'genre_id' => $genres[1]->id,
            'video_id' => $video->id
        ]);
        $this->assertDatabaseHas('videos_genres', [
            'genre_id' => $genres[2]->id,
            'video_id' => $video->id
        ]);
    }

}
