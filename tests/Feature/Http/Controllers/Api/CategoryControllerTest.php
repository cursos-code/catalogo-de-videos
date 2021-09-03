<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Http\Controllers\Api\CategoryController;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{

    use DatabaseMigrations;
    use InvalidationTrait, StoreTrait, ValidateRuleStruct;

    public function testValidationsStruct()
    {
        $this->assertValidationStructRules(CategoryController::class);
    }

    public function testIndex()
    {
        factory(Category::class, 2)->create();
        $response = $this->get(route('categories.index'));

        $response->assertStatus(200)
            ->assertJsonStructure(
                [
                    'data' => [],
                    'links' => [],
                    'meta' => [],
                ]
            )->assertJson(['meta' => ['per_page' => 15]]);

        $resource = CategoryResource::collection(collect([Category::find($response->json('data.id'))]));
        $response->assertJson($resource->response()->getData(true));
    }

    public function testShow()
    {
        $category = factory(Category::class)->create();
        $response = $this->get(route('categories.show', ['category' => $category->id]));

        $response->assertStatus(200);

        $resource = new CategoryResource(Category::find($response->json('data.id')));
        $this->assertResource($response, $resource);
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

        $resource = new CategoryResource(Category::find($response->json('data.id')));
        $this->assertResource($response, $resource);
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

        $resource = new CategoryResource(Category::find($response->json('data.id')));
        $this->assertResource($response, $resource);
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
        foreach ($this->getValidations() as $key => $validation) {
            $this->assertValidationRules('post', $key, $validation);
        }
    }

    public function testInvalidationDataOnPut()
    {
        $category = factory(Category::class)->create();
        foreach ($this->getValidations($category) as $key => $validation) {
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
        return ['data' => ['id', 'name', 'description', 'is_active', 'created_at', 'updated_at', 'deleted_at']];
    }

    protected function getValidations($model = null)
    {
        $data = [
            'name' => 'category',
            'description' => 'description',
            'is_active' => true,
        ];
        return [
            'required' => [
                [
                    'name' => [
                        'route' => route(
                            $model ? 'categories.update' : 'categories.store',
                            $model ? ['category' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['name' => null])
                    ]
                ]
            ],
            'max.string' => [
                [
                    'name' => [
                        'route' => route(
                            $model ? 'categories.update' : 'categories.store',
                            $model ? ['category' => $model->id] : []
                        ),
                        'routeParams' => ['max' => 255],
                        'params' => array_merge($data, ['name' => str_repeat('_', 256)])
                    ]
                ]
            ],
            'boolean' => [
                [
                    'is_active' => [
                        'route' => route(
                            $model ? 'categories.update' : 'categories.store',
                            $model ? ['category' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['is_active' => 3])
                    ]
                ]
            ]
        ];
    }

    public function getStruct()
    {
        return [
            'name' => 'required|max:255',
            'is_active' => 'boolean',
            'description' => 'nullable'
        ];
    }
}
