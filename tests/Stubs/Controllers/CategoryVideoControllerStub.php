<?php

namespace Tests\Stubs\Controllers;

use App\Http\Controllers\BasicCrudController;
use Tests\Stubs\Models\CategoryVideoStub;
use Tests\Stubs\Models\VideoStub;
use Tests\Stubs\Resources\ResourceStub;

class CategoryVideoControllerStub extends BasicCrudController
{

    protected function getModel()
    {
        return CategoryVideoStub::class;
    }

    protected function getRules()
    {
        return [];
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
