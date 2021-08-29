<?php

namespace Tests\Feature\Http\Controllers\Api\Video;

use App\Models\Category;
use App\Models\Genre;
use App\Models\Video;
use Illuminate\Http\UploadedFile;
use Tests\Stubs\Models\VideoStub;

class UploadVideosTest extends BaseVideos
{

    public function testStoreWithFiles()
    {
        $route = route('videos.store');
        $category = Category::create(['name' => 'test', 'description' => 'description']);
        $genre = Genre::create(['name' => 'test']);
        $genre->categories()->sync([$category->id]);
        \Storage::fake();
        $file = UploadedFile::fake()->create('video.mp4')->size(VideoStub::MAX_UPLOAD_VIDEO_SIZE);
        $response = $this->assertStore(
            $route,
            $this->data + [
                'categories_id' => [$category->id],
                'genres_id' => [$genre->id],
                'video_file' => $file
            ],
            $this->data + ['title' => 'video title', 'deleted_at' => null]
        );

        \Storage::assertExists("{$response->json('id')}/{$file->hashName()}");

        $this->assertDateByRegex($response, ['created_at', 'updated_at']);
    }

    public function testUpdateWithFiles()
    {
        $category = factory(Category::class)->create();
        $category2 = factory(Category::class)->create();
        $genre = factory(Genre::class)->create();
        $video = factory(Video::class)->create($this->data);
        $genre->categories()->sync([$category->id, $category2->id]);
        $file = UploadedFile::fake()->create('video.mp4')->size(VideoStub::MAX_UPLOAD_VIDEO_SIZE);
        $route = route('videos.update', ['video' => $video->id]);
        $response = $this->assertUpdate(
            $route,
            array_merge($this->data, [
                'categories_id' => [$category->id, $category2->id],
                'genres_id' => [$genre->id],
                'video_file' => $file
            ]),
            $this->data + ['title' => 'new title', 'deleted_at' => null]
        );

        $this->assertDateByRegex($response, ['created_at', 'updated_at']);
    }



}
