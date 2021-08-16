<?php

namespace Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

class GenreStub extends Model
{
    protected $table = 'genres_stub';
    protected $fillable = ['name', 'is_active'];

    public static function createTable()
    {
        \Schema::create('genres_stub', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public static function dropTable()
    {
        \Schema::dropIfExists('genres_stub');
    }

}
