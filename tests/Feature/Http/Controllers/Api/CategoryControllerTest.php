<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestResponse;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{

    use DatabaseMigrations;
    use InvalidationTrait;

    public function testIndex()
    {
        factory(Category::class, 2)->create();
        $response = $this->get(route('categories.index'));

        $response->assertStatus(200)
            ->assertJson(Category::all()->toArray());
    }

    public function testShow()
    {
        $category = factory(Category::class)->create();
        $response = $this->get(route('categories.show', ['category' => $category->id]));

        $response->assertStatus(200)
            ->assertJson($category->toArray());
    }

    public function testStore()
    {
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->post(route('categories.store'), ['name' => 'name']);
        $response->assertStatus(201);
        $id = $response->json(['id']);
        $response->assertJson(Category::find($id)->toArray());
        $response->assertJsonFragment(['name' => 'name']);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->post(route('categories.store'), ['name' => 'name', 'description' => 'description']);
        $response->assertJsonFragment(['name' => 'name', 'description' => 'description']);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->post(route('categories.store'), ['name' => 'name', 'is_active' => false]);
        $response->assertJsonFragment(['name' => 'name', 'is_active' => false]);
    }

    public function testUpdate()
    {
        $category = factory(Category::class)->create();
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->put(
                route(
                    'categories.update',
                    ['category' => $category->id]
                ),
                ['name' => 'teste']
            );
        $response->assertStatus(200);
        $id = $response->json(['id']);
        $response->assertJson(Category::find($id)->toArray());
        $response->assertJsonFragment(['name' => 'teste']);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->put(route(
                      'categories.update',
                      ['category' => $category->id]
                  ),
                  ['name' => 'name', 'description' => 'descrição']
            );
        $response->assertJsonFragment(['name' => 'name', 'description' => 'descrição']);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->put(
                route(
                    'categories.update',
                    ['category' => $category->id]
                ),
                ['name' => 'name', 'is_active' => false]
            );
        $response->assertJsonFragment(['name' => 'name', 'is_active' => false]);
    }

    public function testDelete()
    {
        $category = factory(Category::class)->create();
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->delete(route('categories.destroy', ['category' => $category->id]), []);
        $response->assertStatus(204);
    }

    public function testInvalidationDataOnPost()
    {
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->post(route('categories.store'), []);
        $this->assertInvalidNameRequired($response);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->post(route('categories.store'), ['name' => str_repeat('_', 256)]);
        $this->assertInvalidNameMax($response);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->post(route('categories.store'), ['name' => str_repeat('_', 255), 'is_active' => 3]);
        $this->assertInvalidIsActive($response);
    }

    public function testInvalidationDataOnPut()
    {
        $category = factory(Category::class)->create();
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->put(route('categories.update', ['category' => $category->id]), ['name' => 'name']);
        $response->assertStatus(200);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->put(route('categories.update', ['category' => $category->id]), []);
        $this->assertInvalidNameRequired($response);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->put(route('categories.update', ['category' => $category->id]), ['name' => str_repeat('_', 256)]);
        $this->assertInvalidNameMax($response);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->put(
                route('categories.update', ['category' => $category->id]),
                ['name' => str_repeat('_', 255), 'is_active' => 3]
            );
        $this->assertInvalidIsActive($response);
    }

    public function testInvalidateOnDelete()
    {
        factory(Category::class)->create();
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->delete(route('categories.destroy', ['category' => Uuid::uuid4()->toString()]), []);
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
