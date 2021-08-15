<?php

namespace Tests\Stubs\Controllers;

use App\Http\Controllers\BasicCrudController;
use Tests\Stubs\Models\CastMemberStub;
use Tests\Stubs\Models\CategoryStub;

class CastMemberControllerStub extends BasicCrudController
{

    protected function getModel()
    {
        return CastMemberStub::class;
    }

    protected function getRules()
    {
        return [
            'name' => 'required|max:255',
            'type' => 'integer'
        ];
    }
}
