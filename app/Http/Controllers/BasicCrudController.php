<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

abstract class BasicCrudController extends Controller
{

    protected $paginationSize = 15;

    protected abstract function getModel();

    protected abstract function getResource();

    protected abstract function getResourceCollection();

    protected abstract function getRules();

    protected abstract function getUpdateRules();

    public function index()
    {
        $data = !$this->paginationSize ? $this->getModel()->all() : $this->getModel()::paginate($this->paginationSize);
        $resourceCollectionClass = $this->getResourceCollection();
        $refClass = new \ReflectionClass($resourceCollectionClass);
        return $refClass->isSubclassOf(ResourceCollection::class)
            ? new $resourceCollectionClass($data)
            : $resourceCollectionClass::collection($data);
    }

    public function show($id)
    {
        $resource = $this->getResource();
        return new $resource($this->findOrFail($id));
    }

    public function store(Request $request)
    {
        $validData = $this->validate($request, $this->getRules());
        $model = $this->getModel()::create($validData);
        $model->refresh();
        $resource = $this->getResource();
        return new $resource($model);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, $this->getUpdateRules());
        $model = $this->findOrFail($id);
        $model->update($request->all());
        $model->refresh();
        $resource = $this->getResource();
        return new $resource($model);
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
