<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BasicCrudController;
use App\Models\Genre;

class GenreController extends BasicCrudController
{

    protected function getModel()
    {
        return Genre::class;
    }

    protected function getRules()
    {
        return [
            'name' => 'required|max:255',
            'is_active' => 'boolean'
        ];
    }
}
