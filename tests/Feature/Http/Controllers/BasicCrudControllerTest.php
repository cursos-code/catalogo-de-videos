<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\BasicCrudController;
use Illuminate\Http\Request;
use Tests\Stubs\StubsConfiguration;
use Tests\TestCase;

class BasicCrudControllerTest extends TestCase
{

    use StubsConfiguration;

    /**
     * @var string[]
     */
    private $stubs;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stubs = $this->getStubs();
    }

    protected function setUp(): void
    {
        parent::setUp();
        foreach ($this->stubs as $stub) {
            $stub['model']::dropTable();
            $stub['model']::createTable();
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->stubs as $stub) {
            $stub['model']::dropTable();
        }
        parent::tearDown();
    }

    public function testIndex()
    {
        foreach ($this->stubs as $stub) {
            $modelStub = $stub['model']::create($stub['create']);
            $this->assertEquals([$modelStub->toArray()], (new $stub['controller'])->index()->toArray());
        }
    }

    public function testInvalidationRules()
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $request = \Mockery::mock(Request::class);
        $request->shouldReceive('all')
            ->once()
            ->andReturn(['names' => '']);
        foreach ($this->stubs as $stub) {
            (new $stub['controller'])->store($request);
        }
    }

    public function testStore()
    {
        foreach ($this->stubs as $stub) {
            $request = \Mockery::mock(Request::class);
            $request->shouldReceive('all')
                ->once()
                ->andReturn($stub['create']);
            $response = (new $stub['controller'])->store($request);
            $this->assertEquals((new $stub['model'])::find(1)->toArray(), $response->toArray());
        }
    }

    public function testFindOrFailFetchModel()
    {
        foreach ($this->stubs as $stub) {
            $modelStub = $stub['model']::create($stub['create']);

            $reflection = new \ReflectionClass(BasicCrudController::class);
            $method = $reflection->getMethod('findOrFail');
            $method->setAccessible(true);

            $result = $method->invokeArgs((new $stub['controller']), [$modelStub->id]);
            $this->assertInstanceOf($stub['model'], $result);
        }
    }

    public function testFindOrFailWithInvalidId()
    {
        $reflection = new \ReflectionClass(BasicCrudController::class);
        $method = $reflection->getMethod('findOrFail');
        $method->setAccessible(true);
        $stub = $this->stubs[rand(0, count($this->stubs) - 1)];

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $result = $method->invokeArgs((new $stub['controller']), [0]);
        $this->assertInstanceOf($stub['model'], $result);
    }

    public function testShow()
    {
        foreach ($this->stubs as $stub) {
            $modelStub = $stub['model']::create($stub['create']);
            $response = (new $stub['controller'])->show($modelStub->id);

            foreach ($stub['create'] as $field) {
                $this->assertEquals($modelStub->{$field}, $response->{$field});
            }
        }
    }

    public function testUpdate()
    {
        foreach ($this->stubs as $stub) {
            $modelStub = $stub['model']::create($stub['create']);
            $request = \Mockery::mock(Request::class);
            $request->shouldReceive('all')
                ->atLeast()
                ->once()
                ->andReturn($modelStub->toArray());
            $response = (new $stub['controller'])->update($request, $modelStub->id);
            $this->assertEquals((new $stub['model'])::find($modelStub->id)->toArray(), $response->toArray());
        }
    }

    public function testDelete()
    {
        foreach ($this->stubs as $stub) {
            $modelStub = $stub['model']::create($stub['create']);
            $response = (new $stub['controller'])->destroy($modelStub->id);
            $this->createTestResponse($response)->assertStatus(204);
            $this->assertCount(0, $modelStub::all());
        }
    }

}
