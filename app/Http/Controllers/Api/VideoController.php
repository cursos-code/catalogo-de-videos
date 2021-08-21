<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BasicCrudController;
use App\Http\Controllers\Traits\TransactionOperations;
use App\Models\Video;
use Illuminate\Http\Request;

class VideoController extends BasicCrudController
{

    use TransactionOperations;

    public function store(Request $request)
    {
        return $this->storeTransaction(Video::class, $request);
    }

    public function update(Request $request, $id)
    {
        return $this->updateTransaction($request, $id);
    }

    protected function getModel()
    {
        return Video::class;
    }

    protected function getRules()
    {
        return [
            'title' => 'required|max:255',
            'description' => 'required',
            'year_launched' => 'required|integer|date_format:Y',
            'opened' => 'boolean',
            'rating' => 'required|in:' . implode(',', Video::RATING_LIST),
            'duration' => 'required|integer',
            'categories_id' => 'required|array|exists:categories,id',
            'genres_id' => 'required|array|exists:genres,id',
        ];
    }

    protected function getUpdateRules()
    {
        return $this->getRules();
    }

    protected function handleRelations($model, Request $request)
    {
        $model->categories()->sync($request->get('categories_id'));
        $model->genres()->sync($request->get('genres_id'));
    }
}
