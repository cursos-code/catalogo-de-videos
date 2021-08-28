<?php

namespace Tests\Unit\Rule;

use Mockery\MockInterface;
use Tests\TestCase;

class GenreHasCategoriesRule extends TestCase
{
    public function testInstanceOfRule()
    {
        $rule = new \App\Rules\GenreHasCategoriesRule([]);
        $this->assertInstanceOf(\Illuminate\Contracts\Validation\Rule::class, $rule);
    }

    public function testCategoriesField()
    {
        $rule = $this->createRuleMock([1, 1, 2, 2]);
        $reflectionCass = new \ReflectionClass(\App\Rules\GenreHasCategoriesRule::class);
        $reflectionProperty = $reflectionCass->getProperty('categoriesId');
        $reflectionProperty->setAccessible(true);

        $categoriesId = $reflectionProperty->getValue($rule);
        $this->assertEqualsCanonicalizing([1, 2], $categoriesId);
    }

    public function testGenresField()
    {
        $rule = $this->createRuleMock([]);
        $rule->shouldReceive('getRows')
            ->withAnyArgs()
            ->andReturn([]);

        $rule->passes('', [1, 1, 2, 2]);
        $reflectionCass = new \ReflectionClass(\App\Rules\GenreHasCategoriesRule::class);
        $reflectionProperty = $reflectionCass->getProperty('genresId');
        $reflectionProperty->setAccessible(true);

        $genresId = $reflectionProperty->getValue($rule);
        $this->assertEqualsCanonicalizing([1, 2], $genresId);
    }

    public function testPassesFailWhenEmptyValues()
    {
        $rule = $this->createRuleMock([1]);
        $this->assertFalse($rule->passes('', []));

        $rule = $this->createRuleMock([]);
        $this->assertFalse($rule->passes('', [1]));
    }

    public function testPassesFailWhenGetRowsEmpty()
    {
        $rule = $this->createRuleMock([1]);
        $rule->shouldReceive('getRows')
            ->withAnyArgs()
            ->andReturn(collect());
        $this->assertFalse($rule->passes('', [1]));
    }

    public function testPassesFailWhenCategoriesHasNoGenres()
    {
        $rule = $this->createRuleMock([1, 2]);
        $rule->shouldReceive('getRows')
            ->withAnyArgs()
            ->andReturn(collect(['category_id' => 1]));
        $this->assertFalse($rule->passes('', [1]));
    }

    public function testPassesFailWhenGenresHasNoCategories()
    {
        $rule = $this->createRuleMock([1]);
        $rule->shouldReceive('getRows')
            ->withAnyArgs()
            ->andReturn(collect([['category_id' => 1], ['category_id' => 2]]));
        $this->assertFalse($rule->passes('', [1, 2]));
    }

    private function createRuleMock(array $categoriesId): MockInterface
    {
        return \Mockery::mock(\App\Rules\GenreHasCategoriesRule::class, [$categoriesId])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
