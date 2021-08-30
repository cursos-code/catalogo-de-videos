<?php

namespace Tests\Feature\Models\Video;

use App\Models\Video;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Tests\Exceptions\TestException;
use Tests\Stubs\Models\Traits\UploadFilesStub;
use Tests\Stubs\Models\VideoStub;
use Tests\TestCase;

class UploadFilesTest extends TestCase
{

    use DatabaseMigrations;

    /**
     * @var UploadFilesStub
     */
    private $uploadtrait;
    /**
     * @var array
     */
    private $data;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uploadtrait = new UploadFilesStub();
        UploadFilesStub::dropTable();
        UploadFilesStub::createTable();
        $this->data = [
            'title' => 'new title',
            'description' => 'video description',
            'year_launched' => 2015,
            'opened' => true,
            'rating' => '18',
            'duration' => 60,
        ];
    }

    public function testCreatingFiles()
    {
        \Storage::fake();
        $video = Video::create(
            $this->data + [
                'video_file' => UploadedFile::fake()->create('video.mp4')->size(VideoStub::MAX_UPLOAD_VIDEO_SIZE),
                'thumb_file' => UploadedFile::fake()->image('imagem.png')->size(VideoStub::MAX_UPLOAD_THUMB_SIZE),
                'banner_file' => UploadedFile::fake()->image('imagem1.png')->size(VideoStub::MAX_UPLOAD_BANNER_SIZE),
                'trailer_file' => UploadedFile::fake()->create('video2.mp4')->size(VideoStub::MAX_UPLOAD_TRAILER_SIZE),
            ]
        );

        \Storage::assertExists("{$video->id}/{$video->video_file}");
        \Storage::assertExists("{$video->id}/{$video->thumb_file}");
        \Storage::assertExists("{$video->id}/{$video->banner_file}");
        \Storage::assertExists("{$video->id}/{$video->trailer_file}");

        foreach (Video::$fileFields as $fileField) {
            $this->assertDatabaseHas('videos', [$fileField => $video->{$fileField}]);
        }
    }

    public function testUpdatingFiles()
    {
        \Storage::fake();
        $video = factory(Video::class)->create();
        $videoFile = UploadedFile::fake()->create('video.mp4')->size(VideoStub::MAX_UPLOAD_VIDEO_SIZE);
        $thumbFile = UploadedFile::fake()->image('imagem.png')->size(VideoStub::MAX_UPLOAD_THUMB_SIZE);
        $bannerFile = UploadedFile::fake()->image('imagem.png')->size(VideoStub::MAX_UPLOAD_BANNER_SIZE);
        $trailerFile = UploadedFile::fake()->create('video2.mp4')->size(VideoStub::MAX_UPLOAD_TRAILER_SIZE);
        $video->update(
            $this->data + [
                'video_file' => $videoFile,
                'thumb_file' => $thumbFile,
                'banner_file' => $bannerFile,
                'trailer_file' => $trailerFile,
            ]
        );

        \Storage::assertExists("{$video->id}/{$video->video_file}");
        \Storage::assertExists("{$video->id}/{$video->thumb_file}");
        \Storage::assertExists("{$video->id}/{$video->banner_file}");
        \Storage::assertExists("{$video->id}/{$video->trailer_file}");

        $newBanner = UploadedFile::fake()->create('video2.mp4')->size(VideoStub::MAX_UPLOAD_VIDEO_SIZE);
        $newTrailer = UploadedFile::fake()->create('video2.mp4')->size(VideoStub::MAX_UPLOAD_VIDEO_SIZE);
        $video->update($this->data + ['banner_file' => $newBanner, 'trailer_file' => $newTrailer]);

        \Storage::assertExists("{$video->id}/{$videoFile->hashName()}");
        \Storage::assertExists("{$video->id}/{$thumbFile->hashName()}");
        \Storage::assertExists("{$video->id}/{$newBanner->hashName()}");
        \Storage::assertExists("{$video->id}/{$newTrailer->hashName()}");
        \Storage::assertMissing("{$video->id}/{$bannerFile->hashName()}");
        \Storage::assertMissing("{$video->id}/{$trailerFile->hashName()}");

        foreach (Video::$fileFields as $fileField) {
            $this->assertDatabaseHas('videos', [$fileField => $video->{$fileField}]);
        }
    }

    public function testCreatingFilesWithRollback()
    {
        $hasError = false;
        \Storage::fake();
        \Event::listen(TransactionCommitted::class, function () {
            throw new TestException();
        });
        try {
            Video::create(
                $this->data + [
                    'video_file' => UploadedFile::fake()->create('video.mp4')->size(VideoStub::MAX_UPLOAD_VIDEO_SIZE),
                    'thumb_file' => UploadedFile::fake()->image('imagem.png')->size(VideoStub::MAX_UPLOAD_THUMB_SIZE),
                ]
            );
        } catch (TestException $e) {
            $hasError = true;
            $this->assertCount(0, \Storage::allFiles());
        }

        $this->assertTrue($hasError);
    }

    public function testMakeOldFilesOnSave()
    {
        $this->uploadtrait->fill(
            [
                'name' => 'test',
                'file1' => 'test.mp4',
                'file2' => 'test.png',
            ]
        );
        $this->uploadtrait->save();
        $this->assertCount(0, $this->uploadtrait->oldFiles);

        $this->uploadtrait->update(
            [
                'name' => 'test update',
                'file2' => 'test1.mp4'
            ]
        );
        $this->assertEqualsCanonicalizing(['test.png'], $this->uploadtrait->oldFiles);
    }

    public function testMakeOldFilesNullOnSave()
    {
        $this->uploadtrait->fill(
            [
                'name' => 'test'
            ]
        );
        $this->uploadtrait->save();
        $this->uploadtrait->update(
            [
                'name' => 'test update',
                'file1' => 'test1.mp4'
            ]
        );
        $this->assertEqualsCanonicalizing([], $this->uploadtrait->oldFiles);
    }

}
