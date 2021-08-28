<?php

namespace Tests\Stubs\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

class UploadFiles extends Model
{
    protected $table = 'categories_stub';
    protected $fillable = ['name', 'description', 'is_active'];

    public static function createTable()
    {
        \Schema::create('categories_stub', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->text('description')->nullable(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public static function dropTable()
    {
        \Schema::dropIfExists('categories_stub');
    }

}
