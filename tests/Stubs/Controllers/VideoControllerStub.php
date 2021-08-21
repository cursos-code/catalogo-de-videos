<?php

namespace Tests\Stubs\Controllers;

use App\Http\Controllers\BasicCrudController;
use Tests\Stubs\Models\VideoStub;

class VideoControllerStub extends BasicCrudController
{

    protected function getModel()
    {
        return VideoStub::class;
    }

    protected function getRules()
    {
        return [
            'title' => 'required|max:255',
            'description' => 'required',
            'year_launched' => 'required|integer',
            'rating' => 'required',
            'duration' => 'required|integer'
        ];
    }

    protected function getUpdateRules()
    {
        return $this->getRules();
    }
}
