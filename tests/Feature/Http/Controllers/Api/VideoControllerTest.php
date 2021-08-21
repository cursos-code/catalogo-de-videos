<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Http\Controllers\Api\VideoController;
use App\Models\Category;
use App\Models\Genre;
use App\Models\Video;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Tests\Exceptions\TestException;
use Tests\TestCase;

class VideoControllerTest extends TestCase
{

    use DatabaseMigrations;
    use InvalidationTrait, StoreTrait, ValidateRuleStruct;

    public function testValidationsStruct()
    {
        $this->assertValidationStructRules(VideoController::class);
    }

    public function testIndex()
    {
        factory(Video::class, 2)->create();
        $response = $this->get(route('videos.index'));

        $response->assertStatus(200)->assertJson(Video::all()->toArray());
    }

    public function testShow()
    {
        $video = factory(Video::class)->create();
        $response = $this->get(route('videos.show', ['video' => $video->id]));

        $response->assertStatus(200)->assertJson($video->toArray());
    }

    public function testStore()
    {
        $route = route('videos.store');
        $category = Category::create(['name' => 'test', 'description' => 'test']);
        $genre = Genre::create(['name' => 'test']);
        $data = [
            'title' => 'video title',
            'description' => 'video description',
            'year_launched' => 2020,
            'opened' => false,
            'rating' => 'L',
            'duration' => 60,
        ];
        $this->assertStore(
            $route,
            $data + [
                'categories_id' => [$category->id],
                'genres_id' => [$genre->id]
            ],
            $data + ['deleted_at' => null]
        );

        $category = Category::create(['name' => 'test', 'description' => 'test']);
        $genre = Genre::create(['name' => 'test']);
        $data = [
            'title' => 'new title',
            'description' => 'video description',
            'year_launched' => 2020,
            'opened' => false,
            'rating' => 'L',
            'duration' => 60
        ];
        $response = $this->assertStore(
            $route,
            $data + [
                'categories_id' => [$category->id],
                'genres_id' => [$genre->id]
            ],
            $data + ['title' => 'video title', 'deleted_at' => null]
        );

        $this->assertDateByRegex($response, ['created_at', 'updated_at']);
    }

    public function testUpdate()
    {
        $category = Category::create(['name' => 'test', 'description' => 'test']);
        $genre = Genre::create(['name' => 'test']);
        $video = Video::create([
                                   'title' => 'video title',
                                   'description' => 'video description',
                                   'year_launched' => 2020,
                                   'opened' => false,
                                   'rating' => 'L',
                                   'duration' => 60,
                                   'categories_id' => [$category->id],
                                   'genres_id' => [$genre->id]
                               ]);
        $route = route('videos.update', ['video' => $video->id]);
        $data = [
            'title' => 'new title',
            'description' => 'video description',
            'year_launched' => 2015,
            'opened' => true,
            'rating' => '18',
            'duration' => 60,
        ];
        $response = $this->assertUpdate(
            $route,
            $data + [
                'categories_id' => [$category->id],
                'genres_id' => [$genre->id]
            ],
            $data + ['title' => 'new title', 'deleted_at' => null]
        );

        $this->assertDateByRegex($response, ['created_at', 'updated_at']);
    }

    public function testDelete()
    {
        $video = factory(Video::class)->create();
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->delete(route('videos.destroy', ['video' => $video->id]), []);
        $response->assertStatus(204);
    }

    public function testInvalidateOnDelete()
    {
        factory(Video::class)->create();
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->delete(route('videos.destroy', ['video' => Uuid::uuid4()->toString()]), []);
        $response->assertStatus(404);
    }

    public function testRollbackOnStore()
    {
        $data = [
            'title' => 'new title',
            'description' => 'video description',
            'year_launched' => 2015,
            'opened' => true,
            'rating' => '18',
            'duration' => 60,
            'categories_id' => [1],
            'genres_id' => [1]
        ];
        $controller = \Mockery::mock(VideoController::class)
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
        $genre = Genre::create(['name' => 'test']);
        $data = [
            'title' => 'video title',
            'description' => 'video description',
            'year_launched' => 2020,
            'opened' => false,
            'rating' => 'L',
            'duration' => 60,
            'categories_id' => [$category->id],
            'genres_id' => [$genre->id],
        ];
        $response = $this->withHeaders(['Accept' => 'application/json'])->post(route('videos.store'), $data);
        $video = Video::find($response->json('id'));

        $response->assertStatus(201);
        $this->assertCount(1, $video->categories->toArray());
        $this->assertEquals($video->categories->toArray()[0]['id'], $category->id);
        $this->assertEquals($video->genres->toArray()[0]['id'], $genre->id);

        $newCategory = Category::create(['name' => 'test', 'description' => 'test']);
        $newGenre = Genre::create(['name' => 'test']);
        $newData = array_merge($data, [
            'title' => 'new video title',
            'categories_id' => [$category->id, $newCategory->id],
            'genres_id' => [$genre->id, $newGenre->id],
        ]);

        $response = $this->withHeaders(['Accept' => 'application/json'])->put(
            route('videos.update', ['video' => $video->id]),
            $newData
        );
        $response->assertStatus(200);
        $video = Video::find($response->json('id'));

        $categoryGroup = [$category, $newCategory];
        $genreGroup = [$genre, $newGenre];
        sort($categoryGroup);
        sort($genreGroup);

        $this->assertCount(2, $video->categories->toArray());
        $cats = $video->categories->toArray();
        $gens = $video->genres->toArray();
        sort($cats);
        sort($gens);
        for ($i = 0; $i < count($categoryGroup); $i++) {
            $this->assertEquals($cats[$i]['id'], $categoryGroup[$i]->id);
            $this->assertEquals($gens[$i]['id'], $genreGroup[$i]->id);
        }
    }

    public function testInvalidationDataOnPost()
    {
        foreach ($this->getValidations() as $key => $validation) {
            $this->assertValidationRules('post', $key, $validation);
        }
    }

    public function testInvalidationDataOnPut()
    {
        $video = factory(Video::class)->create();
        foreach ($this->getValidations($video) as $key => $validation) {
            $this->assertValidationRules('put', $key, $validation);
        }
    }

    protected function getModel()
    {
        return Video::class;
    }

    protected function getStructure(): array
    {
        return [
            'title',
            'description',
            'year_launched',
            'opened',
            'rating',
            'duration',
            'created_at',
            'updated_at',
            'deleted_at'
        ];
    }

    private function getValidations($model = null)
    {
        $category = Category::create(['name' => 'test', 'description' => 'description']);
        $genre = Genre::create(['name' => 'test']);
        $data = [
            'title' => 'new title',
            'description' => 'video description',
            'year_launched' => 2015,
            'opened' => true,
            'rating' => '18',
            'duration' => 60,
            'categories_id' => [$category->id],
            'genres_id' => [$genre->id],
        ];
        return [
            'required' => [
                [
                    'title' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['title' => null])
                    ],
                    'description' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['description' => null])
                    ],
                    'year_launched' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['year_launched' => null])
                    ],
                    'rating' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['rating' => null])
                    ],
                    'duration' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['duration' => null])
                    ],
                    'categories_id' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['categories_id' => null])
                    ],
                    'genres_id' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['genres_id' => null])
                    ]
                ]
            ],
            'max.string' => [
                [
                    'title' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'routeParams' => ['max' => 255],
                        'params' => array_merge($data, ['title' => str_repeat('_', 256)])
                    ]
                ]
            ],
            'integer' => [
                [
                    'year_launched' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['year_launched' => 'null'])
                    ],
                    'duration' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['duration' => 'null'])
                    ]
                ]
            ],
            'in' => [
                [
                    'rating' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['rating' => '33'])
                    ]
                ]
            ],
            'date_format' => [
                [
                    'year_launched' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'routeParams' => ['format' => 'Y'],
                        'params' => array_merge($data, ['year_launched' => 32875])
                    ]
                ]
            ],
            'array' => [
                [
                    'categories_id' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['categories_id' => true])
                    ],
                    'genres_id' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['genres_id' => true])
                    ]
                ]
            ],
            'exists' => [
                [
                    'categories_id' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['categories_id' => ['test']])
                    ],
                    'genres_id' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['genres_id' => ['test']])
                    ]
                ]
            ],
        ];
    }

    public function getStruct()
    {
        return [
            'title' => 'required|max:255',
            'description' => 'required',
            'year_launched' => 'required|integer|date_format:Y',
            'opened' => 'boolean',
            'rating' => 'required|in:' . implode(',', Video::RATING_LIST),
            'duration' => 'required|integer',
            'categories_id' => 'required|array|exists:categories,id',
            'genres_id' => 'required|array|exists:genres,id',
        ];
    }
}
