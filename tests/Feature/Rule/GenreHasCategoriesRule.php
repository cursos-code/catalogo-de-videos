<?php

namespace Tests\Feature\Rule;

use App\Models\Category;
use App\Models\Genre;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class GenreHasCategoriesRule extends TestCase
{

    use DatabaseMigrations;

    private $categories;
    private $genres;

    protected function setUp(): void
    {
        parent::setUp();
        $this->categories = factory(Category::class, 4)->create();
        $this->genres = factory(Genre::class, 2)->create();
        $this->genres[0]->categories()->sync(
            [
                $this->categories[0]->id,
                $this->categories[1]->id,
            ]
        );
        $this->genres[1]->categories()->sync(
            [
                $this->categories[2]->id
            ]
        );
    }

    public function testPassesIsValid()
    {
        $rule = new \App\Rules\GenreHasCategoriesRule([$this->categories[2]->id]);
        $isValid = $rule->passes('', [$this->genres[1]->id]);
        $this->assertTrue($isValid);

        $rule = new \App\Rules\GenreHasCategoriesRule([$this->categories[0]->id, $this->categories[2]->id]);
        $isValid = $rule->passes('', [$this->genres[0]->id, $this->genres[1]->id]);
        $this->assertTrue($isValid);

        $rule = new \App\Rules\GenreHasCategoriesRule(
            [$this->categories[0]->id, $this->categories[1]->id, $this->categories[2]->id]
        );
        $isValid = $rule->passes('', [$this->genres[0]->id, $this->genres[1]->id]);
        $this->assertTrue($isValid);

        $isValid = $rule->passes(
            '',
            [
                $this->genres[0]->id,
                $this->genres[1]->id,
                $this->genres[0]->id,
                $this->genres[1]->id
            ]
        );
        $this->assertTrue($isValid);
    }

    public function testPassesIsInvalid()
    {
        $rule = new \App\Rules\GenreHasCategoriesRule([$this->categories[0]->id]);
        $isValid = $rule->passes('', [$this->genres[0]->id, $this->genres[1]->id]);
        $this->assertFalse($isValid);

        $rule = new \App\Rules\GenreHasCategoriesRule([$this->categories[3]->id]);
        $isValid = $rule->passes('', [$this->genres[0]->id, $this->genres[1]->id]);
        $this->assertFalse($isValid);
    }

}
