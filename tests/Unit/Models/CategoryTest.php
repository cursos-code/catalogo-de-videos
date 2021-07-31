<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Traits\Uuid;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class CategoryTest extends TestCase
{

    /**
     * @var Category
     */
    private $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = new Category();
    }

    public function testIfUseTraits()
    {
        $traits = [
            SoftDeletes::class,
            Uuid::class
        ];
        $classTraits = array_keys(class_uses(Category::class));
        self::assertEquals($traits, $classTraits);
    }

    public function testIfPropertiesAreCorrect()
    {
        $fillable = ['name', 'description', 'is_active'];
        self::assertEquals($fillable, $this->category->getFillable());
        $dates = ['deleted_at', 'created_at', 'updated_at'];
        foreach ($this->category->getDates() as $date) {
            self::assertContains($date, $dates);
        }
        $casts = ['id' => 'string', 'is_active' => 'boolean'];
        self::assertEqualsCanonicalizing($casts, $this->category->getCasts());
        $incrementing = false;
        self::assertEquals($incrementing, $this->category->getIncrementing());
    }

}
