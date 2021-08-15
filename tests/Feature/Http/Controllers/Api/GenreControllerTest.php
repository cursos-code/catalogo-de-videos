<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Genre;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class GenreControllerTest extends TestCase
{

    use DatabaseMigrations;
    use InvalidationTrait, StoreTrait;

    public function testIndex()
    {
        factory(Genre::class, 2)->create();
        $response = $this->get(route('genres.index'));

        $response->assertStatus(200)->assertJson(Genre::all()->toArray());
    }

    public function testShow()
    {
        $genre = factory(Genre::class)->create();
        $response = $this->get(route('genres.show', ['genre' => $genre->id]));

        $response->assertStatus(200)->assertJson($genre->toArray());
    }

    public function testStore()
    {
        $route = route('genres.store');
        $data = ['name' => 'name'];
        $this->assertStore($route, $data, $data + ['is_active' => true, 'deleted_at' => null]);

        $data = ['name' => 'name', 'is_active' => false];
        $this->assertStore($route, $data, $data + ['deleted_at' => null]);
    }

    public function testUpdate()
    {
        $genre = Genre::create(['name' => 'test']);
        $route = route(
            'genres.update',
            ['genre' => $genre->id]
        );
        $data = ['name' => 'teste'];
        $this->assertUpdate(
            $route,
            $data,
            $data + ['is_active' => true, 'deleted_at' => null]
        );

        $data = ['name' => 'more name', 'is_active' => false];
        $response = $this->assertUpdate($route, $data, $data + ['deleted_at' => null]);

        $this->assertDateByRegex($response, ['created_at', 'updated_at']);
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
        $validations = [
            'required' => [
                [
                    'name' => [
                        'route' => route('genres.store'),
                        'params' => []
                    ]
                ]
            ],
            'max.string' => [
                [
                    'name' => [
                        'route' => route('genres.store'),
                        'routeParams' => ['max' => 255],
                        'params' => ['name' => str_repeat('_', 256)]
                    ]
                ]
            ],
            'boolean' => [
                [
                    'is_active' => [
                        'route' => route('genres.store'),
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
        $genre = factory(Genre::class)->create();
        $validations = [
            'required' => [
                [
                    'name' => [
                        'route' => route('genres.update', ['genre' => $genre->id]),
                        'params' => ['name' => '']
                    ]
                ]
            ],
            'max.string' => [
                [
                    'name' => [
                        'route' => route('genres.update', ['genre' => $genre->id]),
                        'routeParams' => ['max' => 255],
                        'params' => ['name' => str_repeat('_', 256)]
                    ]
                ]
            ],
            'boolean' => [
                [
                    'is_active' => [
                        'route' => route('genres.update', ['genre' => $genre->id]),
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
        return ['id', 'name', 'is_active', 'created_at', 'updated_at', 'deleted_at'];
    }
}
