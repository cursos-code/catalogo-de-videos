<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Http\Controllers\Api\GenreController;
use App\Http\Resources\GenreResource;
use App\Models\Category;
use App\Models\Genre;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Tests\Exceptions\TestException;
use Tests\TestCase;

class GenreControllerTest extends TestCase
{

    use DatabaseMigrations;
    use InvalidationTrait, StoreTrait, ValidateRuleStruct;

    public function testValidationsStruct()
    {
        $this->assertValidationStructRules(GenreController::class);
    }

    public function testIndex()
    {
        factory(Genre::class, 2)->create();
        $response = $this->get(route('genres.index'));

        $response->assertStatus(200)
            ->assertJsonStructure(
                [
                    'data' => [],
                    'links' => [],
                    'meta' => [],
                ]
            )->assertJson(['meta' => ['per_page' => 15]]);

        $resource = GenreResource::collection(collect([Genre::find($response->json('data.id'))]));
        $response->assertJson($resource->response()->getData(true));
    }

    public function testShow()
    {
        $genre = factory(Genre::class)->create();
        $response = $this->get(route('genres.show', ['genre' => $genre->id]));

        $response->assertStatus(200);

        $resource = new GenreResource(Genre::find($response->json('data.id')));
        $this->assertResource($response, $resource);
    }

    public function testStore()
    {
        $route = route('genres.store');
        $category = factory(Category::class)->create();
        $data = ['name' => 'name'];
        $response = $this->assertStore(
            $route,
            $data + [
                'categories_id' => [$category->id]
            ],
            $data + ['is_active' => true, 'deleted_at' => null]
        );

        $this->assertDateByRegex($response, ['created_at', 'updated_at']);

        $resource = new GenreResource(Genre::find($response->json('data.id')));
        $this->assertResource($response, $resource);
    }

    public function testUpdate()
    {
        $genre = Genre::create(['name' => 'test']);
        $category = factory(Category::class)->create();
        $route = route(
            'genres.update',
            ['genre' => $genre->id]
        );
        $data = ['name' => 'teste'];
        $this->assertUpdate(
            $route,
            $data + [
                'categories_id' => [$category->id]
            ],
            $data + ['is_active' => true, 'deleted_at' => null]
        );

        $data = ['name' => 'more name', 'is_active' => false];
        $response = $this->assertUpdate(
            $route,
            $data + [
                'categories_id' => [$category->id]
            ],
            $data + ['deleted_at' => null]
        );

        $this->assertDateByRegex($response, ['created_at', 'updated_at']);
    }

    public function testDelete()
    {
        $genre = factory(Genre::class)->create();
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->delete(route('genres.destroy', ['genre' => $genre->id]), []);

        $response->assertStatus(204);
    }

    public function testInvalidateStoreWithDeletedCategory()
    {
        $route = route('genres.store');
        $category = Category::create(['name' => 'test', 'description' => 'test']);
        $category->delete();
        $data = [
            'name' => 'name',
            'is_active' => false,
            'categories_id' => [$category->id],
        ];
        $response = $this->withHeaders(['Accept' => 'application/json'])->post($route, $data);
        $response->assertJsonValidationErrors(['categories_id']);
        $response->assertJsonFragment(
            [
                \Lang::get("validation.exists", ['attribute' => 'categories id'])
            ]
        );
    }

    public function testInvalidateUpdateWithDeletedCategory()
    {
        $route = route('genres.store');
        $category = Category::create(['name' => 'test', 'description' => 'test']);
        $data = [
            'name' => 'name',
            'is_active' => false,
            'categories_id' => [$category->id]
        ];
        $result = $this->withHeaders(['Accept' => 'application/json'])->post($route, $data)->json('data');

        $route = route('genres.update', ['genre' => $result['id']]);
        $newCategory = Category::create(['name' => 'test', 'description' => 'test']);
        $category->delete();
        $response = $this->withHeaders(['Accept' => 'application/json'])->put(
            $route,
            array_merge($data, [
                'categories_id' => [$category->id, $newCategory->id]
            ])
        );

        $response->assertJsonValidationErrors(['categories_id']);
        $response->assertJsonFragment(
            [
                \Lang::get("validation.exists", ['attribute' => 'categories id'])
            ]
        );
    }

    public function testCategoriesSync()
    {
        $route = route('genres.store');
        $categories = factory(Category::class, 3)->create()->pluck('id')->toArray();
        $data = [
            'name' => 'name',
            'is_active' => false,
            'categories_id' => [$categories[0]]
        ];
        $response = $this->withHeaders(['Accept' => 'application/json'])->post($route, $data);
        $this->assertDatabaseHas('genres_categories', [
            'category_id' => $categories[0],
            'genre_id' => $response->json('data.id')
        ]);

        $route = route('genres.update', ['genre' => $response->json('data.id')]);
        $response = $this->withHeaders(['Accept' => 'application/json'])->put(
            $route,
            array_merge($data, [
                'categories_id' => [$categories[1], $categories[2]]
            ])
        );
        $this->assertDatabaseMissing('genres_categories', [
            'category_id' => $categories[0],
            'genre_id' => $response->json('data.id')
        ]);
        $this->assertDatabaseHas('genres_categories', [
            'category_id' => $categories[1],
            'genre_id' => $response->json('data.id')
        ]);
        $this->assertDatabaseHas('genres_categories', [
            'category_id' => $categories[2],
            'genre_id' => $response->json('data.id')
        ]);
    }

    public function testInvalidationDataOnPost()
    {
        foreach ($this->getValidations() as $key => $validation) {
            $this->assertValidationRules('post', $key, $validation);
        }
    }

    public function testRollbackOnStore()
    {
        $data = ['name' => 'teste', 'categories_id' => [1],];
        $controller = \Mockery::mock(GenreController::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $controller->shouldReceive('getRules')
            ->withAnyArgs()
            ->andReturn([]);
        $controller->shouldReceive('validate')
            ->withAnyArgs()
            ->andReturn($data);
        $controller->shouldReceive('handleRelations')
            ->once()
            ->andThrow(new TestException(''));

        $request = \Mockery::mock(Request::class);

        try {
            $controller->store($request);
        } catch (TestException $e) {
            self::assertCount(0, $controller->index());
        }
    }

    public function testRelations()
    {
        $category = Category::create(['name' => 'test', 'description' => 'test']);
        $data = [
            'name' => 'genre name',
            'categories_id' => [$category->id]
        ];
        $response = $this->withHeaders(['Accept' => 'application/json'])->post(route('genres.store'), $data);
        $genre = Genre::find($response->json('data.id'));

        $response->assertStatus(201);
        $this->assertCount(1, $genre->categories->toArray());
        $this->assertEquals($genre->categories->toArray()[0]['id'], $category->id);

        $newCategory = Category::create(['name' => 'test', 'description' => 'test']);
        $newData = array_merge($data, [
            'name' => 'new genre name',
            'categories_id' => [$category->id, $newCategory->id]
        ]);

        $response = $this->withHeaders(['Accept' => 'application/json'])->put(
            route('genres.update', ['genre' => $genre->id]),
            $newData
        );

        $response->assertStatus(200);
        $genre = Genre::find($response->json('data.id'));

        $categoryGroup = [$category, $newCategory];
        sort($categoryGroup);

        $this->assertCount(2, $genre->categories->toArray());
        $cats = $genre->categories->toArray();
        sort($cats);
        for ($i = 0; $i < count($categoryGroup); $i++) {
            $this->assertEquals($cats[$i]['id'], $categoryGroup[$i]->id);
        }
    }

    public function testInvalidationDataOnPut()
    {
        $genre = factory(Genre::class)->create();
        foreach ($this->getValidations($genre) as $key => $validation) {
            $this->assertValidationRules('put', $key, $validation);
        }
    }

    public function testInvalidateOnDelete()
    {
        factory(Genre::class)->create();
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->delete(route('genres.destroy', ['genre' => Uuid::uuid4()->toString()]), []);
        $response->assertStatus(404);
    }

    protected function getModel()
    {
        return Genre::class;
    }

    protected function getStructure(): array
    {
        return ['data' => ['id', 'name', 'is_active', 'created_at', 'updated_at', 'deleted_at']];
    }

    protected function getValidations($model = null)
    {
        $data = [
            'name' => 'genre',
            'is_active' => true
        ];
        return [
            'required' => [
                [
                    'name' => [
                        'route' => route(
                            $model ? 'genres.update' : 'genres.store',
                            $model ? ['genre' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['name' => null])
                    ]
                ]
            ],
            'max.string' => [
                [
                    'name' => [
                        'route' => route(
                            $model ? 'genres.update' : 'genres.store',
                            $model ? ['genre' => $model->id] : []
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
                            $model ? 'genres.update' : 'genres.store',
                            $model ? ['genre' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['is_active' => 3])
                    ]
                ]
            ],
            'array' => [
                [
                    'categories_id' => [
                        'route' => route(
                            $model ? 'genres.update' : 'genres.store',
                            $model ? ['genre' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['categories_id' => true])
                    ],
                ]
            ],
            'exists' => [
                [
                    'categories_id' => [
                        'route' => route(
                            $model ? 'genres.update' : 'genres.store',
                            $model ? ['genre' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['categories_id' => ['test']])
                    ],
                ]
            ],
        ];
    }

    public function getStruct()
    {
        return [
            'name' => 'required|max:255',
            'is_active' => 'boolean',
            'categories_id' => 'required|array|exists:categories,id,deleted_at,NULL',
        ];
    }
}
