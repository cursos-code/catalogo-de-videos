<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BasicCrudController;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CategoryController extends BasicCrudController
{

    protected function getModel()
    {
        return Category::class;
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

}
