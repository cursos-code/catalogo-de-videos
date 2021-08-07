<?php

namespace Tests\Feature\Models;

use App\Models\Genre;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use DatabaseMigrations;

    public function testGenreFields()
    {
        factory(Genre::class, 1)->create();
        $genres = Genre::all();
        $this->assertCount(1, $genres);
        $genresKeys = array_keys($genres->first()->getAttributes());
        $this->assertEqualsCanonicalizing(
            [
                'id',
                'name',
                'is_active',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            $genresKeys
        );
    }

    public function testCreatingNewGenre()
    {
        /** @var Genre $genre */
        $genre = Genre::create(['name' => 'test']);
        $genre->refresh();
        $this->assertTrue(Uuid::isValid($genre->id));
        $this->assertEquals('test', $genre->name);
        $this->assertTrue($genre->is_active);

        $genre = Genre::create(['name' => 'test', 'is_active' => false]);
        $this->assertFalse($genre->is_active);
    }

    public function testEditingGenre()
    {
        /** @var Genre $genre */
        $genre = factory(Genre::class)->create(
            ['is_active' => false]
        )->first();
        $data = ['name' => 'test', 'is_active' => true];
        $genre->update($data);

        $this->assertTrue(Uuid::isValid($genre->id));
        foreach ($data as $key => $value) {
            $this->assertEquals($value, $genre->{$key});
        }
    }

    public function testExcludingGenre()
    {
        $genres = factory(Genre::class, 2)->create(
            ['is_active' => false]
        );
        $this->assertCount(2, $genres);
        /** @var Genre $genre */
        $genre = $genres->first();
        $genre->delete();
        $this->assertCount(2, Genre::withTrashed()->get());
        $this->assertCount(1, Genre::all());
        $genre->restore();
        $this->assertCount(2, Genre::all());
    }
}
