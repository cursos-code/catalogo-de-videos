<?php

namespace Tests\Stubs\Models\Traits;

use App\Models\Traits\UploadFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

class UploadFilesStub extends Model
{
    use UploadFiles;

    protected $fillable = ['name', 'file1', 'file2'];
    public static $fileFields = ['file1', 'file2'];
    protected $table = 'upload_file_stub';

    public static function createTable()
    {
        \Schema::create('upload_file_stub', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('file1')->nullable();
            $table->string('file2')->nullable();
            $table->timestamps();
        });
    }

    public static function dropTable()
    {
        \Schema::dropIfExists('upload_file_stub');
    }

    protected function uploadDir()
    {
        return '1';
    }
}
