<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BasicCrudController;
use App\Http\Controllers\Traits\TransactionOperations;
use App\Http\Resources\GenreResource;
use App\Models\Genre;
use Illuminate\Http\Request;

class GenreController extends BasicCrudController
{

    use TransactionOperations;

    public function store(Request $request)
    {
        $a = $this->storeTransaction(Genre::class, $request);
        return $a;
    }

    public function update(Request $request, $id)
    {
        return $this->updateTransaction($request, $id);
    }

    protected function handleRelations($model, Request $request)
    {
        $model->categories()->sync($request->get('categories_id'));
    }

    protected function getModel()
    {
        return Genre::class;
    }

    protected function getRules()
    {
        return [
            'name' => 'required|max:255',
            'is_active' => 'boolean',
            'categories_id' => 'required|array|exists:categories,id,deleted_at,NULL',
        ];
    }

    protected function getUpdateRules()
    {
        return $this->getRules();
    }

    protected function getResource()
    {
        return GenreResource::class;
    }

    protected function getResourceCollection()
    {
        return $this->getResource();
    }
}
