<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use DatabaseMigrations;

    public function testCategoryFields()
    {
        factory(Category::class, 1)->create();
        $categoties = Category::all();
        $this->assertCount(1, $categoties);
        $categotiesKeys = array_keys($categoties->first()->getAttributes());
        $this->assertEqualsCanonicalizing(
            [
                'id',
                'name',
                'description',
                'is_active',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            $categotiesKeys
        );
    }

    public function testCreatingNewCategory()
    {
        /** @var Category $category */
        $category = Category::create(['name' => 'test']);
        $category->refresh();
        $this->assertTrue(Uuid::isValid($category->id));
        $this->assertEquals('test', $category->name);
        $this->assertNull($category->description);
        $this->assertTrue($category->is_active);

        $category = Category::create(['name' => 'test', 'description' => 'test description']);
        $this->assertEquals('test description', $category->description);

        $category = Category::create(['name' => 'test', 'is_active' => false]);
        $this->assertFalse($category->is_active);
    }

    public function testEditingCategory()
    {
        /** @var Category $category */
        $category = factory(Category::class)->create(
            ['description' => 'test description', 'is_active' => false]
        )->first();
        $data = ['name' => 'test', 'description' => 'test of description', 'is_active' => true];
        $category->update($data);

        $this->assertTrue(Uuid::isValid($category->id));
        foreach ($data as $key => $value) {
            $this->assertEquals($value, $category->{$key});
        }
    }

    public function testExcludingCategory()
    {
        $categories = factory(Category::class, 2)->create(
            ['description' => 'test description', 'is_active' => false]
        );
        $this->assertCount(2, $categories);
        /** @var Category $category */
        $category = $categories->first();
        $category->delete();
        $this->assertCount(2, Category::withTrashed()->get());
        $this->assertCount(1, Category::all());
    }
}
