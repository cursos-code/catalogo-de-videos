<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class BasicCrudController extends Controller
{

    protected abstract function getModel();

    protected abstract function getRules();

    protected abstract function getUpdateRules();

    public function index()
    {
        return $this->getModel()::all();
    }

    public function store(Request $request)
    {
        $validData = $this->validate($request, $this->getRules());
        $model = $this->getModel()::create($validData);
        $model->refresh();
        return $model;
    }

    public function show($id)
    {
        return $this->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, $this->getUpdateRules());
        $model = $this->findOrFail($id);
        $model->update($request->all());
        $model->refresh();
        return $model;
    }

    public function destroy($id)
    {
        $model = $this->findOrFail($id);
        $model->delete();
        return response()->noContent();
    }

    protected function findOrFail($id)
    {
        $model = $this->getModel();
        $keyname = (new $model)->getRouteKeyName();
        return $this->getModel()::where($keyname, $id)->firstOrFail();
    }
}
