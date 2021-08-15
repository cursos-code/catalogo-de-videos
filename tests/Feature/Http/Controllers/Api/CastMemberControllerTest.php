<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\CastMember;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class CastMemberControllerTest extends TestCase
{

    use DatabaseMigrations;
    use InvalidationTrait, StoreTrait;

    public function testIndex()
    {
        factory(CastMember::class, 2)->create();
        $response = $this->get(route('castMembers.index'));

        $response->assertStatus(200)->assertJson(CastMember::all()->toArray());
    }

    public function testShow()
    {
        $castMember = factory(CastMember::class)->create();
        $response = $this->get(route('castMembers.show', ['castMember' => $castMember->id]));

        $response->assertStatus(200)->assertJson($castMember->toArray());
    }

    public function testStore()
    {
        $route = route('castMembers.store');
        $data = ['name' => 'name', 'type' => 1];
        $this->assertStore($route, $data, $data + ['type' => 1, 'deleted_at' => null]);

        $data = ['name' => 'name', 'type' => 2];
        $response = $this->assertStore($route, $data, $data + ['type' => 2, 'deleted_at' => null]);

        $this->assertDateByRegex($response, ['created_at', 'updated_at']);
    }

    public function testUpdate()
    {
        $castMember = CastMember::create(['name' => 'test', 'type' => 1]);
        $route = route('castMembers.update', ['castMember' => $castMember->id]);
        $data = ['name' => 'teste', 'type' => 2];
        $this->assertUpdate(
            $route,
            $data,
            $data + ['type' => 1, 'deleted_at' => null]
        );

        $data = ['name' => 'teste', 'type' => 2];
        $response = $this->assertUpdate(
            $route,
            $data,
            $data + ['type' => 2, 'deleted_at' => null]
        );

        $this->assertDateByRegex($response, ['created_at', 'updated_at']);
    }

    public function testDelete()
    {
        $castMember = factory(CastMember::class)->create();
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->delete(route('castMembers.destroy', ['castMember' => $castMember->id]), []);
        $response->assertStatus(204);
    }

    public function testInvalidationDataOnPost()
    {
        $validations = [
            'required' => [
                [
                    'name' => [
                        'route' => route('castMembers.store'),
                        'params' => ['type' => 1]
                    ],
                    'type' => [
                        'route' => route('castMembers.store'),
                        'params' => ['name' => 'test']
                    ]
                ]
            ],
            'max.string' => [
                [
                    'name' => [
                        'route' => route('castMembers.store'),
                        'routeParams' => ['max' => 255],
                        'params' => ['name' => str_repeat('_', 256)]
                    ]
                ]
            ],
            'integer' => [
                [
                    'type' => [
                        'route' => route('castMembers.store'),
                        'params' => ['name' => str_repeat('_', 255), 'type' => 'string']
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
        $castMember = factory(CastMember::class)->create();
        $validations = [
            'required' => [
                [
                    'name' => [
                        'route' => route('castMembers.update', ['castMember' => $castMember->id]),
                        'params' => ['type' => 1]
                    ],
                    'type' => [
                        'route' => route('castMembers.update', ['castMember' => $castMember->id]),
                        'params' => ['name' => 'test']
                    ]
                ]
            ],
            'max.string' => [
                [
                    'name' => [
                        'route' => route('castMembers.update', ['castMember' => $castMember->id]),
                        'routeParams' => ['max' => 255],
                        'params' => ['name' => str_repeat('_', 256)]
                    ]
                ]
            ],
            'integer' => [
                [
                    'type' => [
                        'route' => route('castMembers.update', ['castMember' => $castMember->id]),
                        'params' => ['name' => str_repeat('_', 20), 'type' => 'string']
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
        factory(CastMember::class)->create();
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->delete(route('castMembers.destroy', ['castMember' => Uuid::uuid4()->toString()]), []);
        $response->assertStatus(404);
    }

    protected function getModel()
    {
        return CastMember::class;
    }

    protected function getStructure(): array
    {
        return ['id', 'name', 'type', 'created_at', 'updated_at', 'deleted_at'];
    }
}
