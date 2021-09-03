<?php

namespace Tests\Stubs\Controllers;

use App\Http\Controllers\BasicCrudController;
use Tests\Stubs\Models\GenreStub;
use Tests\Stubs\Resources\ResourceStub;

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

    protected function getResource()
    {
        return ResourceStub::class;
    }

    protected function getResourceCollection()
    {
        return $this->getResource();
    }
}
