<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Http\Controllers\Api\CastMemberController;
use App\Http\Resources\CastMemberResource;
use App\Models\CastMember;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class CastMemberControllerTest extends TestCase
{

    use DatabaseMigrations;
    use InvalidationTrait, StoreTrait, ValidateRuleStruct;

    public function testValidationsStruct()
    {
        $this->assertValidationStructRules(CastMemberController::class);
    }

    public function testIndex()
    {
        factory(CastMember::class, 2)->create();
        $response = $this->get(route('cast_members.index'));

        $response->assertStatus(200)
            ->assertJsonStructure(
                [
                    'data' => [],
                    'links' => [],
                    'meta' => [],
                ]
            )->assertJson(['meta' => ['per_page' => 15]]);

        $resource = CastMemberResource::collection(collect([CastMember::find($response->json('data.id'))]));
        $response->assertJson($resource->response()->getData(true));
    }

    public function testShow()
    {
        $castMember = factory(CastMember::class)->create();
        $response = $this->get(route('cast_members.show', ['cast_member' => $castMember->id]));

        $response->assertStatus(200);

        $resource = new CastMemberResource(CastMember::find($response->json('data.id')));
        $this->assertResource($response, $resource);
    }

    public function testStore()
    {
        $route = route('cast_members.store');
        $data = ['name' => 'name', 'type' => CastMember::TYPE_ACTOR];
        $this->assertStore($route, $data, $data + ['type' => CastMember::TYPE_ACTOR, 'deleted_at' => null]);

        $data = ['name' => 'name', 'type' => CastMember::TYPE_DIRECTOR];
        $response = $this->assertStore(
            $route,
            $data,
            $data + ['type' => CastMember::TYPE_DIRECTOR, 'deleted_at' => null]
        );

        $this->assertDateByRegex($response, ['created_at', 'updated_at']);

        $resource = new CastMemberResource(CastMember::find($response->json('data.id')));
        $this->assertResource($response, $resource);
    }

    public function testUpdate()
    {
        $castMember = CastMember::create(['name' => 'test', 'type' => CastMember::TYPE_ACTOR]);
        $route = route('cast_members.update', ['cast_member' => $castMember->id]);
        $data = ['name' => 'teste', 'type' => CastMember::TYPE_DIRECTOR];
        $this->assertUpdate(
            $route,
            $data,
            $data + ['type' => CastMember::TYPE_DIRECTOR, 'deleted_at' => null]
        );

        $data = ['name' => 'teste', 'type' => CastMember::TYPE_ACTOR];
        $response = $this->assertUpdate(
            $route,
            $data,
            $data + ['type' => CastMember::TYPE_ACTOR, 'deleted_at' => null]
        );

        $this->assertDateByRegex($response, ['created_at', 'updated_at']);
    }

    public function testDelete()
    {
        $castMember = factory(CastMember::class)->create();
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->delete(route('cast_members.destroy', ['cast_member' => $castMember->id]), []);
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
        $castMember = factory(CastMember::class)->create();
        foreach ($this->getValidations($castMember) as $key => $validation) {
            $this->assertValidationRules('put', $key, $validation);
        }
    }

    public function testInvalidateOnDelete()
    {
        factory(CastMember::class)->create();
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->delete(route('cast_members.destroy', ['cast_member' => Uuid::uuid4()->toString()]), []);
        $response->assertStatus(404);
    }

    protected function getModel()
    {
        return CastMember::class;
    }

    protected function getStructure(): array
    {
        return ['data' => ['id', 'name', 'type', 'created_at', 'updated_at', 'deleted_at']];
    }

    protected function getValidations($model = null)
    {
        $data = [
            'name' => 'cast member',
            'type' => CastMember::TYPE_ACTOR
        ];
        return [
            'required' => [
                [
                    'name' => [
                        'route' => route(
                            $model ? 'cast_members.update' : 'cast_members.store',
                            $model ? ['cast_member' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['name' => null])
                    ],
                    'type' => [
                        'route' => route(
                            $model ? 'cast_members.update' : 'cast_members.store',
                            $model ? ['cast_member' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['type' => null])
                    ]
                ]
            ],
            'max.string' => [
                [
                    'name' => [
                        'route' => route(
                            $model ? 'cast_members.update' : 'cast_members.store',
                            $model ? ['cast_member' => $model->id] : []
                        ),
                        'routeParams' => ['max' => 255],
                        'params' => array_merge($data, ['name' => str_repeat('_', 256)])
                    ]
                ]
            ],
            'integer' => [
                [
                    'type' => [
                        'route' => route(
                            $model ? 'cast_members.update' : 'cast_members.store',
                            $model ? ['cast_member' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['type' => 'string'])
                    ]
                ]
            ],
            'in' => [
                [
                    'type' => [
                        'route' => route(
                            $model ? 'cast_members.update' : 'cast_members.store',
                            $model ? ['cast_member' => $model->id] : []
                        ),
                        'params' => array_merge($data, ['type' => 3])
                    ]
                ]
            ]
        ];
    }

    public function getStruct()
    {
        return [
            'name' => 'required|max:255',
            'type' => 'required|integer|in:'.implode(',', [CastMember::TYPE_ACTOR, CastMember::TYPE_DIRECTOR])
        ];
    }
}
