<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Http\Controllers\Api\VideoController;
use App\Models\Category;
use App\Models\Genre;
use App\Models\Video;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\Uuid;
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
        $category = factory(Category::class)->create(['name' => 'test', 'description' => 'test']);
        $genre = factory(Genre::class)->create(['name' => 'test']);
        $genre->categories()->sync([$category->id]);
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

    public function testStoreWithFiles()
    {
        $route = route('videos.store');
        $category = Category::create(['name' => 'test', 'description' => 'description']);
        $genre = Genre::create(['name' => 'test']);
        $genre->categories()->sync([$category->id]);
        \Storage::fake();
        $file = UploadedFile::fake()->create('video.mp4')->size(Video::MAX_UPLOAD_SIZE);
        $data = [
            'title' => 'new title',
            'description' => 'video description',
            'year_launched' => 2015,
            'opened' => true,
            'rating' => '18',
            'duration' => 60,
        ];
        $response = $this->assertStore(
            $route,
            $data + [
                'categories_id' => [$category->id],
                'genres_id' => [$genre->id],
                'video_file' => $file
            ],
            $data + ['title' => 'video title', 'deleted_at' => null]
        );

        \Storage::assertExists("{$response->json('id')}/{$file->hashName()}");

        $this->assertDateByRegex($response, ['created_at', 'updated_at']);
    }

    public function testUpdate()
    {
        $category = factory(Category::class)->create();
        $category2 = factory(Category::class)->create();
        $genre = factory(Genre::class)->create();
        $video = factory(Video::class)->create(
            [
                'title' => 'video title',
                'description' => 'video description',
                'year_launched' => 2020,
                'opened' => false,
                'rating' => 'L',
                'duration' => 60,
            ]
        );
        $genre->categories()->sync([$category->id, $category2->id]);
        $route = route('videos.update', ['video' => $video->id]);
        $data = [
            'title' => 'new title',
            'description' => 'video description',
            'year_launched' => 2015,
            'opened' => true,
            'rating' => '18',
            'duration' => 60
        ];
        $response = $this->assertUpdate(
            $route,
            array_merge($data, [
                'categories_id' => [$category->id, $category2->id],
                'genres_id' => [$genre->id]
            ]),
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

    public function testInvalidateStoreWithDeletedCategory()
    {
        $route = route('videos.store');
        $category = Category::create(['name' => 'test', 'description' => 'test']);
        $genre = Genre::create(['name' => 'test']);
        $category->delete();
        $data = [
            'title' => 'video title',
            'description' => 'video description',
            'year_launched' => 2020,
            'opened' => false,
            'rating' => 'L',
            'duration' => 60,
            'categories_id' => [$category->id],
            'genres_id' => [$genre->id]
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
        $route = route('videos.store');
        $category = factory(Category::class)->create(['name' => 'test', 'description' => 'test']);
        $genre = factory(Genre::class)->create(['name' => 'test']);
        $genre->categories()->sync([$category->id]);
        $data = [
            'title' => 'video title',
            'description' => 'video description',
            'year_launched' => 2020,
            'opened' => false,
            'rating' => 'L',
            'duration' => 60,
            'categories_id' => [$category->id],
            'genres_id' => [$genre->id]
        ];
        $result = $this->withHeaders(['Accept' => 'application/json'])->post($route, $data)->json();

        $route = route('videos.update', ['video' => $result['id']]);
        $newCategory = factory(Category::class)->create(['name' => 'test', 'description' => 'test']);
        $category->delete();
        $genre->categories()->sync([$category->id, $newCategory->id]);
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

    /*public function testRelations()
    {
        $category = Category::create(['name' => 'test', 'description' => 'test']);
        $genre = Genre::create(['name' => 'test']);
        $genre->categories()->sync([$category->id]);
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
        $genre->categories()->sync([$category->id, $newCategory->id]);
        $newGenre->categories()->sync([$category->id, $newCategory->id]);
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
    }*/

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
        $genre->categories()->sync([$category->id]);
        $file = UploadedFile::fake()->create('video.mp4')->size(Video::MAX_UPLOAD_SIZE + 1);
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
            'genre_has_categories' => [
                [
                    'genres_id' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['categories_id' => ['test']])
                    ]
                ]
            ],
            'mimetypes' => [
                [
                    'video_file' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'routeParams' => ['values' => 'video/mp4'],
                        'params' => array_merge($data, ['video_file' => 'video.mp3'])
                    ]
                ]
            ],
            'max.file' => [
                [
                    'video_file' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'routeParams' => ['max' => Video::MAX_UPLOAD_SIZE],
                        'params' => array_merge($data, ['video_file' => $file])
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
            'categories_id' => 'required|array|exists:categories,id,deleted_at,NULL',
            'genres_id' => ['required', 'array', 'exists:genres,id,deleted_at,NULL'],
            'video_file' => 'nullable|mimetypes:video/mp4|max:' . Video::MAX_UPLOAD_SIZE
        ];
    }
}
