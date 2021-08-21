<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait TransactionOperations
{

    protected abstract function handleRelations(Model $model, Request $request);

    protected function storeTransaction($modelClass, Request $request)
    {
        $validData = $this->validate($request, $this->getRules());
        $self = $this;
        $model = \DB::transaction(function () use ($modelClass, $validData, $request, $self) {
            $model = $modelClass::create($validData);
            $self->handleRelations($model, $request);
            return $model;
        });
        $model->refresh();
        return $model;
    }

    protected function updateTransaction(Request $request, $id)
    {
        $this->validate($request, $this->getUpdateRules());
        $model = $this->findOrFail($id);
        $self = $this;
        \DB::transaction(function () use ($id, $request, $self, $model) {
            $model->update($request->all());
            $self->handleRelations($model, $request);
            return $model;
        });
        $model->refresh();
        return $model;
    }

}
