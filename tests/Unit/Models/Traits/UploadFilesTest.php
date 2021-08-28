<?php

namespace Tests\Unit\Models;

use Illuminate\Http\UploadedFile;
use Tests\Stubs\Models\Traits\UploadFilesStub;
use Tests\TestCase;

class UploadFilesTest extends TestCase
{

    /**
     * @var UploadFilesStub
     */
    private $uploadtrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uploadtrait = new UploadFilesStub();
    }

    public function testUploadFile()
    {
        \Storage::fake();
        $file = UploadedFile::fake()->create('video.mp4');
        $this->uploadtrait->uploadFile($file);
        \Storage::assertExists('1', $file->hashName());
    }

    public function testUploadFiles()
    {
        \Storage::fake();
        $file1 = UploadedFile::fake()->create('video.mp4');
        $file2 = UploadedFile::fake()->create('video2.mp4');
        $this->uploadtrait->uploadFiles([$file1, $file2]);
        \Storage::assertExists("1/{$file1->hashName()}");
        \Storage::assertExists("1/{$file2->hashName()}");
    }

    public function testDeleteFile()
    {
        \Storage::fake();
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
        \Storage::fake();
        $file1 = UploadedFile::fake()->create('video.mp4');
        $file2 = UploadedFile::fake()->create('video2.mp4');
        $this->uploadtrait->deleteFiles([$file1, $file2->hashName()]);
        \Storage::assertMissing("1/{$file1->hashName()}");
        \Storage::assertMissing("1/{$file2->hashName()}");
    }

    public function testExtractFiles()
    {
        $attributes = [];
        $files = $this->uploadtrait::extractFiles($attributes);
        $this->assertCount(0, $attributes);
        $this->assertCount(0, $files);

        $attributes = ['file1' => 'test'];
        $files = $this->uploadtrait::extractFiles($attributes);
        $this->assertEquals(['file1' => 'test'], $attributes);
        $this->assertCount(1, $attributes);
        $this->assertCount(0, $files);

        $attributes = ['file1' => 'test', 'file2' => 'test'];
        $files = $this->uploadtrait::extractFiles($attributes);
        $this->assertEquals(['file1' => 'test', 'file2' => 'test'], $attributes);
        $this->assertCount(2, $attributes);
        $this->assertCount(0, $files);

        $file1 = UploadedFile::fake()->create('video1.mp4');
        $attributes = ['file1' => $file1, 'other' => 'test'];
        $files = $this->uploadtrait::extractFiles($attributes);
        $this->assertEquals(['file1' => $file1->hashName(), 'other' => 'test'], $attributes);
        $this->assertCount(2, $attributes);
        $this->assertEquals([$file1], $files);

        $file2 = UploadedFile::fake()->create('video1.mp4');
        $attributes = ['file1' => $file1, 'file2' => $file2, 'other' => 'test'];
        $files = $this->uploadtrait::extractFiles($attributes);
        $this->assertEquals([
                                'file1' => $file1->hashName(),
                                'file2' => $file2->hashName(),
                                'other' => 'test'
                            ], $attributes);
        $this->assertCount(3, $attributes);
        $this->assertEquals([$file1, $file2], $files);
    }

}
