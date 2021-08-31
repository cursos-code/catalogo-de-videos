<?php

namespace Tests\Feature\Http\Controllers\Api\Video;

use App\Http\Controllers\Api\VideoController;
use App\Http\Resources\VideoResource;
use App\Models\Category;
use App\Models\Genre;
use App\Models\Video;
use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\Uuid;
use Tests\Feature\Http\Controllers\Api\InvalidationTrait;
use Tests\Feature\Http\Controllers\Api\ValidateRuleStruct;

class VideoControllerTest extends BaseVideos
{

    use InvalidationTrait, ValidateRuleStruct;

    public function testValidationsStruct()
    {
        $this->assertValidationStructRules(VideoController::class);
    }

    public function testIndex()
    {
        factory(Video::class, 2)->create();
        $response = $this->get(route('videos.index'));

        $response->assertStatus(200)
            ->assertJsonStructure(
                [
                    'data' => [],
                    'links' => [],
                    'meta' => [],
                ]
            )->assertJson(['meta' => ['per_page' => 15]]);

        $resource = VideoResource::collection(collect([Video::find($response->json('data.id'))]));
        $response->assertJson($resource->response()->getData(true));
    }

    public function testShow()
    {
        $video = factory(Video::class)->create();
        $response = $this->get(route('videos.show', ['video' => $video->id]));

        $response->assertStatus(200);

        $resource = new VideoResource(Video::find($response->json('data.id')));
        $this->assertResource($response, $resource);
    }

    public function testStore()
    {
        $route = route('videos.store');
        $category = factory(Category::class)->create(['name' => 'test', 'description' => 'test']);
        $genre = factory(Genre::class)->create(['name' => 'test']);
        $genre->categories()->sync([$category->id]);
        $response = $this->assertStore(
            $route,
            $this->data + [
                'categories_id' => [$category->id],
                'genres_id' => [$genre->id]
            ],
            $this->data + ['title' => 'video title', 'deleted_at' => null]
        );

        $this->assertDateByRegex($response, ['created_at', 'updated_at']);
    }

    public function testUpdate()
    {
        $category = factory(Category::class)->create();
        $category2 = factory(Category::class)->create();
        $genre = factory(Genre::class)->create();
        $video = factory(Video::class)->create($this->data);
        $genre->categories()->sync([$category->id, $category2->id]);
        $route = route('videos.update', ['video' => $video->id]);
        $response = $this->assertUpdate(
            $route,
            array_merge($this->data, [
                'categories_id' => [$category->id, $category2->id],
                'genres_id' => [$genre->id]
            ]),
            $this->data + ['title' => 'new title', 'deleted_at' => null]
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
        $data = array_merge($this->data, [
            'categories_id' => [$category->id],
            'genres_id' => [$genre->id]
        ]);
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
        $data = array_merge($this->data, [
            'categories_id' => [$category->id],
            'genres_id' => [$genre->id]
        ]);
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

    private function getValidations($model = null)
    {
        $category = Category::create(['name' => 'test', 'description' => 'description']);
        $genre = Genre::create(['name' => 'test']);
        $genre->categories()->sync([$category->id]);
        $data = $this->data + [
                'categories_id' => [$category->id],
                'genres_id' => [$genre->id]
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
            'image' => [
                [
                    'banner_file' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'params' => array_merge($data, [
                            'banner_file' => 'image.mp3'
                        ])
                    ],
                    'trailer_file' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'params' => array_merge($data, [
                            'banner_file' => 'image.mp3'
                        ])
                    ]
                ],
            ],
            'max.file' => [
                [
                    'video_file' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'routeParams' => ['max' => Video::MAX_UPLOAD_VIDEO_SIZE],
                        'params' => array_merge($data, [
                            'video_file' => UploadedFile::fake()->create('video.mp4')->size(
                                Video::MAX_UPLOAD_VIDEO_SIZE + 1
                            )
                        ])
                    ],
                    'thumb_file' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'routeParams' => ['max' => Video::MAX_UPLOAD_THUMB_SIZE],
                        'params' => array_merge($data, [
                            'thumb_file' => UploadedFile::fake()->create('video.mp4')->size(
                                Video::MAX_UPLOAD_THUMB_SIZE + 1
                            )
                        ])
                    ],
                    'banner_file' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'routeParams' => ['max' => Video::MAX_UPLOAD_BANNER_SIZE],
                        'params' => array_merge($data, [
                            'banner_file' => UploadedFile::fake()->image('image.png')->size(
                                Video::MAX_UPLOAD_BANNER_SIZE + 1
                            )
                        ])
                    ],
                    'trailer_file' => [
                        'route' => route(
                            $model ? 'videos.update' : 'videos.store',
                            $model ? ['video' => $model->id] : []
                        ),
                        'routeParams' => ['max' => Video::MAX_UPLOAD_TRAILER_SIZE],
                        'params' => array_merge($data, [
                            'trailer_file' => UploadedFile::fake()->image('image.png')->size(
                                Video::MAX_UPLOAD_TRAILER_SIZE + 1
                            )
                        ])
                    ]
                ]
            ],
        ];
    }


}
