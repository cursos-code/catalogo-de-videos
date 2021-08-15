<?php

namespace Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

class CastMemberStub extends Model
{
    protected $table = 'cast_members_stub';
    protected $fillable = ['name', 'type'];

    public static function createTable()
    {
        \Schema::create('cast_members_stub', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->smallInteger('type')->nullable(false);
            $table->timestamps();
        });
    }

    public static function dropTable()
    {
        \Schema::dropIfExists('cast_members_stub');
    }

}
