<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Genre;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestResponse;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class GenreControllerTest extends TestCase
{

    use DatabaseMigrations;
    use InvalidationTrait;

    public function testIndex()
    {
        factory(Genre::class, 2)->create();
        $response = $this->get(route('genres.index'));

        $response->assertStatus(200)
            ->assertJson(Genre::all()->toArray());
    }

    public function testShow()
    {
        $genre = factory(Genre::class)->create();
        $response = $this->get(route('genres.show', ['genre' => $genre->id]));

        $response->assertStatus(200)
            ->assertJson($genre->toArray());
    }

    public function testStore()
    {
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->post(route('genres.store'), ['name' => 'name']);
        $response->assertStatus(201);
        $id = $response->json(['id']);
        $response->assertJson(Genre::find($id)->toArray());
        $response->assertJsonFragment(['name' => 'name']);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->post(route('genres.store'), ['name' => 'name']);
        $response->assertJsonFragment(['name' => 'name']);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->post(route('genres.store'), ['name' => 'name', 'is_active' => false]);
        $response->assertJsonFragment(['name' => 'name', 'is_active' => false]);
    }

    public function testUpdate()
    {
        $genre = factory(Genre::class)->create();
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->put(
                route(
                    'genres.update',
                    ['genre' => $genre->id]
                ),
                ['name' => 'teste']
            );
        $response->assertStatus(200);
        $id = $response->json(['id']);
        $response->assertJson(Genre::find($id)->toArray());
        $response->assertJsonFragment(['name' => 'teste']);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->put(route(
                      'genres.update',
                      ['genre' => $genre->id]
                  ),
                  ['name' => 'name']
            );
        $response->assertJsonFragment(['name' => 'name']);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->put(
                route(
                    'genres.update',
                    ['genre' => $genre->id]
                ),
                ['name' => 'name', 'is_active' => false]
            );
        $response->assertJsonFragment(['name' => 'name', 'is_active' => false]);
    }

    public function testDelete()
    {
        $genre = factory(Genre::class)->create();
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->delete(route('genres.destroy', ['genre' => $genre->id]), []);
        $response->assertStatus(204);
    }

    public function testInvalidationDataOnPost()
    {
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->post(route('genres.store'), []);
        $this->assertInvalidNameRequired($response);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->post(route('genres.store'), ['name' => str_repeat('_', 256)]);
        $this->assertInvalidNameMax($response);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->post(route('genres.store'), ['name' => str_repeat('_', 255), 'is_active' => 3]);
        $this->assertInvalidIsActive($response);
    }

    public function testInvalidationDataOnPut()
    {
        $genre = factory(Genre::class)->create();
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->put(route('genres.update', ['genre' => $genre->id]), ['name' => 'name']);
        $response->assertStatus(200);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->put(route('genres.update', ['genre' => $genre->id]), []);
        $this->assertInvalidNameRequired($response);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->put(route('genres.update', ['genre' => $genre->id]), ['name' => str_repeat('_', 256)]);
        $this->assertInvalidNameMax($response);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->put(
                route('genres.update', ['genre' => $genre->id]),
                ['name' => str_repeat('_', 255), 'is_active' => 3]
            );
        $this->assertInvalidIsActive($response);
    }

    public function testInvalidateOnDelete()
    {
        factory(Genre::class)->create();
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->delete(route('genres.destroy', ['genre' => Uuid::uuid4()->toString()]), []);
        $response->assertStatus(404);
    }

    private function assertInvalidNameRequired(TestResponse $response)
    {
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJsonMissingValidationErrors(['is_active']);
        $response->assertJsonFragment([\Lang::get('validation.required', ['attribute' => 'name'])]);
    }

    private function assertInvalidNameMax(TestResponse $response)
    {
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJsonMissingValidationErrors(['is_active']);
        $response->assertJsonFragment([\Lang::get('validation.max.string', ['attribute' => 'name', 'max' => 255])]);
    }

    private function assertInvalidIsActive(TestResponse $response)
    {
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_active'])
            ->assertJsonMissingValidationErrors(['name']);
        $response->assertJsonFragment([\Lang::get('validation.boolean', ['attribute' => 'is active'])]);
    }


}
