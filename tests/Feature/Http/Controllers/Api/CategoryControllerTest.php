<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{

    use DatabaseMigrations;
    use InvalidationTrait, StoreTrait;

    public function testIndex()
    {
        factory(Category::class, 2)->create();
        $response = $this->get(route('categories.index'));

        $response->assertStatus(200)->assertJson(Category::all()->toArray());
    }

    public function testShow()
    {
        $category = factory(Category::class)->create();
        $response = $this->get(route('categories.show', ['category' => $category->id]));

        $response->assertStatus(200)->assertJson($category->toArray());
    }

    public function testStore()
    {
        $route = route('categories.store');
        $data = ['name' => 'name'];
        $this->assertStore(
            $route,
            $data,
            $data + ['description' => null, 'is_active' => true, 'deleted_at' => null]
        );

        $data = ['name' => 'name', 'description' => 'description'];
        $this->assertStore($route, $data, $data + ['is_active' => true, 'deleted_at' => null]);

        $data = ['name' => 'name', 'is_active' => false];
        $this->assertStore($route, $data, $data + ['description' => null, 'deleted_at' => null]);

        $data = ['name' => 'name', 'description' => 'description', 'is_active' => false];
        $response = $this->assertStore($route, $data, $data + ['deleted_at' => null]);

        $this->assertDateByRegex($response, ['created_at', 'updated_at']);
    }

    public function testUpdate()
    {
        $category = Category::create(['name' => 'test']);
        $route = route('categories.update', ['category' => $category->id]);
        $data = ['name' => 'teste', 'description' => null];
        $this->assertUpdate(
            $route,
            $data,
            $data + ['description' => null, 'is_active' => true, 'deleted_at' => null]
        );

        $data = ['name' => 'name', 'description' => 'description'];
        $this->assertUpdate(
            $route,
            $data,
            $data + ['description' => 'description', 'is_active' => true, 'deleted_at' => null]
        );

        $data = ['name' => 'more name', 'is_active' => false];
        $response = $this->assertUpdate($route, $data, $data + ['deleted_at' => null]);

        $this->assertDateByRegex($response, ['created_at', 'updated_at']);
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
        $validations = [
            'required' => [
                [
                    'name' => [
                        'route' => route('categories.store'),
                        'params' => []
                    ]
                ]
            ],
            'max.string' => [
                [
                    'name' => [
                        'route' => route('categories.store'),
                        'routeParams' => ['max' => 255],
                        'params' => ['name' => str_repeat('_', 256)]
                    ]
                ]
            ],
            'boolean' => [
                [
                    'is_active' => [
                        'route' => route('categories.store'),
                        'params' => ['name' => str_repeat('_', 255), 'is_active' => 3]
                    ]
                ]
            ]
        ];
        foreach ($validations as $key => $validation) {
            $this->assertValidationRules('post', $key, $validation);
        }
    }

    public function testInvalidationDataOnPut()
    {
        $category = factory(Category::class)->create();
        $validations = [
            'required' => [
                [
                    'name' => [
                        'route' => route('categories.update', ['category' => $category->id]),
                        'params' => ['name' => '']
                    ]
                ]
            ],
            'max.string' => [
                [
                    'name' => [
                        'route' => route('categories.update', ['category' => $category->id]),
                        'routeParams' => ['max' => 255],
                        'params' => ['name' => str_repeat('_', 256)]
                    ]
                ]
            ],
            'boolean' => [
                [
                    'is_active' => [
                        'route' => route('categories.update', ['category' => $category->id]),
                        'params' => ['is_active' => 3]
                    ]
                ]
            ]
        ];
        foreach ($validations as $key => $validation) {
            $this->assertValidationRules('put', $key, $validation);
        }
    }

    public function testInvalidateOnDelete()
    {
        factory(Category::class)->create();
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->delete(route('categories.destroy', ['category' => Uuid::uuid4()->toString()]), []);
        $response->assertStatus(404);
    }

    protected function getModel()
    {
        return Category::class;
    }

    protected function getStructure(): array
    {
        return ['id', 'name', 'description', 'is_active', 'created_at', 'updated_at', 'deleted_at'];
    }
}
