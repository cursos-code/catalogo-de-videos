<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\BasicCrudController;
use Illuminate\Http\Request;
use Tests\Stubs\Controllers\CategoryControllerStub;
use Tests\Stubs\Models\CategoryStub;
use Tests\TestCase;

class BasicCrudControllerTest extends TestCase
{

    /**
     * @var CategoryControllerStub
     */
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();
        CategoryStub::dropTable();
        CategoryStub::createTable();
        $this->controller = new CategoryControllerStub();
    }

    protected function tearDown(): void
    {
        CategoryStub::dropTable();
        parent::tearDown();
    }

    public function testIndex()
    {
        /** @var CategoryStub $category */
        $category = CategoryStub::create(['name' => 'test', 'description' => 'description', 'is_active' => true]);

        $this->assertEquals([$category->toArray()], $this->controller->index()->toArray());
    }

    public function testInvalidationRules()
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $request = \Mockery::mock(Request::class);
        $request->shouldReceive('all')
            ->once()
            ->andReturn(['name' => '']);
        $this->controller->store($request);
    }

    public function testStore()
    {
        $request = \Mockery::mock(Request::class);
        $request->shouldReceive('all')
            ->once()
            ->andReturn(['name' => 'test', 'description' => 'description', 'is_active' => false]);
        $model = $this->controller->store($request);
        $this->assertEquals(CategoryStub::find(1)->toArray(), $model->toArray());
    }

    public function testFindOrFailFetchModel()
    {
        /** @var CategoryStub $category */
        $category = CategoryStub::create(['name' => 'test', 'description' => 'description', 'is_active' => true]);

        $reflection = new \ReflectionClass(BasicCrudController::class);
        $method = $reflection->getMethod('findOrFail');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->controller, [$category->id]);
        $this->assertInstanceOf(CategoryStub::class, $result);
    }

    public function testFindOrFailWithInvalidId()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $reflection = new \ReflectionClass(BasicCrudController::class);
        $method = $reflection->getMethod('findOrFail');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->controller, [0]);
        $this->assertInstanceOf(CategoryStub::class, $result);
    }

    public function testShow()
    {
        /** @var CategoryStub $category */
        $category = CategoryStub::create(['name' => 'test', 'description' => 'description', 'is_active' => true]);

        $model = $this->controller->show($category->id);
        $this->assertEquals($category->id, $model->id);
        $this->assertEquals($category->name, $model->name);
    }

    public function testUpdate()
    {
        /** @var CategoryStub $category */
        $category = CategoryStub::create(['name' => 'test', 'description' => 'description', 'is_active' => true]);

        $request = \Mockery::mock(Request::class);
        $request->shouldReceive('all')
            ->atLeast()
            ->once()
            ->andReturn($category->toArray());
        $model = $this->controller->update($request, $category->id);
        $this->assertEquals(CategoryStub::find($category->id)->toArray(), $model->toArray());
    }

    public function testDelete()
    {
        /** @var CategoryStub $category */
        $category = CategoryStub::create(['name' => 'test', 'description' => 'description', 'is_active' => true]);

        $model = $this->controller->destroy($category->id);
        $this->assertEquals(204, $model->status());
    }

}
