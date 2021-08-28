<?php

namespace Tests\Stubs\Models\Traits;

use App\Models\Traits\UploadFiles;

class UploadFilesStub
{
    use UploadFiles;

    public static $fileFields = ['file1', 'file2'];

    protected function uploadDir()
    {
        return '1';
    }
}
