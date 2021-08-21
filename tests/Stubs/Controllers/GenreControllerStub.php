<?php

namespace Tests\Stubs\Controllers;

use App\Http\Controllers\BasicCrudController;
use Tests\Stubs\Models\GenreStub;

class GenreControllerStub extends BasicCrudController
{

    protected function getModel()
    {
        return GenreStub::class;
    }

    protected function getRules()
    {
        return [
            'name' => 'required|max:255',
            'is_active' => 'boolean'
        ];
    }

    protected function getUpdateRules()
    {
        return $this->getRules();
    }
}
