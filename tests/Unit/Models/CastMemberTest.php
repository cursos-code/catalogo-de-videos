<?php

namespace Tests\Unit\Models;

use App\Models\CastMember;
use App\Models\Traits\Uuid;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class CastMemberTest extends TestCase
{

    /**
     * @var CastMember
     */
    private $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = new CastMember();
    }

    public function testIfUseTraits()
    {
        $traits = [
            SoftDeletes::class,
            Uuid::class
        ];
        $classTraits = array_keys(class_uses(CastMember::class));
        $this->assertEquals($traits, $classTraits);
    }

    public function testIfPropertiesAreCorrect()
    {
        $fillable = ['name', 'type'];
        $this->assertEquals($fillable, $this->category->getFillable());
        $dates = ['deleted_at', 'created_at', 'updated_at'];
        foreach ($this->category->getDates() as $date) {
            $this->assertContains($date, $dates);
        }
        $casts = ['id' => 'string', 'type' => 'integer'];
        $this->assertEqualsCanonicalizing($casts, $this->category->getCasts());
        $incrementing = false;
        $this->assertEquals($incrementing, $this->category->getIncrementing());
    }

}
