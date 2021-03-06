<?php

namespace Tests\Stubs\Controllers;

use App\Http\Controllers\BasicCrudController;
use Tests\Stubs\Models\CategoryStub;
use Tests\Stubs\Resources\ResourceStub;

class CategoryControllerStub extends BasicCrudController
{

    protected function getModel()
    {
        return CategoryStub::class;
    }

    protected function getRules()
    {
        return [
            'name' => 'required|max:255',
            'is_active' => 'boolean',
            'description' => 'nullable'
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
