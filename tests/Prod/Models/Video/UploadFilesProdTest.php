<?php

namespace Tests\Prod\Models\Video;

use Illuminate\Http\UploadedFile;
use Tests\Stubs\Models\Traits\UploadFilesStub;
use Tests\TestCase;
use Tests\Traits\TestInProd;

class UploadFilesProdTest extends TestCase
{

    use TestInProd;

    /**
     * @var UploadFilesStub
     */
    private $uploadtrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skipTestsIfNotInProd();
        $this->uploadtrait = new UploadFilesStub();
        \Config::set('filesystems.default', 'gcs');
        $this->deleteAllFiles();
    }

    private function deleteAllFiles()
    {
        $dirs = \Storage::directories();
        foreach ($dirs as $dir) {
            $files = \Storage::allFiles();
            \Storage::delete($files);
            \Storage::deleteDirectory($dir);
        }
    }

    public function testUploadFile()
    {
        $file = UploadedFile::fake()->create('video.mp4');
        $this->uploadtrait->uploadFile($file);
        \Storage::assertExists('1', $file->hashName());
    }

    public function testUploadFiles()
    {
        $file1 = UploadedFile::fake()->create('video.mp4');
        $file2 = UploadedFile::fake()->create('video2.mp4');
        $this->uploadtrait->uploadFiles([$file1, $file2]);
        \Storage::assertExists("1/{$file1->hashName()}");
        \Storage::assertExists("1/{$file2->hashName()}");
    }

    public function testDeleteFile()
    {
        $file = UploadedFile::fake()->create('video.mp4');
        $this->uploadtrait->uploadFile($file);
        $this->uploadtrait->deleteFile($file->hashName());
        \Storage::assertMissing("1/{$file->hashName()}");

        $file = UploadedFile::fake()->create('video.mp4');
        $this->uploadtrait->uploadFile($file);
        $this->uploadtrait->deleteFile($file);
        \Storage::assertMissing("1/{$file->hashName()}");
    }

    public function testDeleteFiles()
    {
        $file1 = UploadedFile::fake()->create('video.mp4');
        $file2 = UploadedFile::fake()->create('video2.mp4');
        $this->uploadtrait->deleteFiles([$file1, $file2->hashName()]);
        \Storage::assertMissing("1/{$file1->hashName()}");
        \Storage::assertMissing("1/{$file2->hashName()}");
    }

    public function testDeleteOldFile()
    {
        $file1 = UploadedFile::fake()->create('video.mp4');
        $file2 = UploadedFile::fake()->create('video.mp4');
        $this->uploadtrait->uploadFile($file1);
        $this->uploadtrait->uploadFile($file2);
        $this->uploadtrait->deleteOldFiles();
        $this->assertCount(2, \Storage::allFiles());

        $this->uploadtrait->oldFiles = [$file1->hashName()];
        $this->uploadtrait->deleteOldFiles();
        \Storage::assertMissing("1/{$file1->hashName()}");
        \Storage::assertExists("1/{$file2->hashName()}");
    }

}
