<?php

namespace Tests\Unit\Models;

use App\Models\Genre;
use App\Models\Traits\Uuid;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class GenreTest extends TestCase
{

    /**
     * @var Genre
     */
    private $genre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->genre = new Genre();
    }

    public function testIfUseTraits()
    {
        $traits = [
            SoftDeletes::class,
            Uuid::class
        ];
        $classTraits = array_keys(class_uses(Genre::class));
        $this->assertEquals($traits, $classTraits);
    }

    public function testIfPropertiesAreCorrect()
    {
        $fillable = ['name', 'is_active'];
        $this->assertEquals($fillable, $this->genre->getFillable());
        $dates = ['deleted_at', 'created_at', 'updated_at'];
        foreach ($this->genre->getDates() as $date) {
            $this->assertContains($date, $dates);
        }
        $casts = ['id' => 'string', 'is_active' => 'boolean', 'categories_id' => 'array'];
        $this->assertEqualsCanonicalizing($casts, $this->genre->getCasts());
        $incrementing = false;
        $this->assertEquals($incrementing, $this->genre->getIncrementing());
    }

}
