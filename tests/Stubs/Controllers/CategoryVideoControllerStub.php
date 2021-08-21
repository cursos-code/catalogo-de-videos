<?php

namespace Tests\Stubs\Controllers;

use App\Http\Controllers\BasicCrudController;
use Tests\Stubs\Models\CategoryVideoStub;
use Tests\Stubs\Models\VideoStub;

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
}
