<?php

namespace Coderstm\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;

trait HasResourceActions
{
    protected ?string $model = null;

    protected function useModel(string|object $model): void
    {
        $this->model = is_object($model) ? get_class($model) : ltrim($model, '\\');
    }

    protected function getModelClass(): string
    {
        if (! empty($this->model)) {
            return $this->model;
        }
        $controllerClass = get_class($this);
        $modelName = Str::replaceLast('Controller', '', class_basename($controllerClass));
        $namespace = Str::beforeLast($controllerClass, '\\');
        $baseNamespace = Str::beforeLast($namespace, '\\');
        $modelClass = Str::replaceLast('Controllers', 'Models', $baseNamespace).'\\'.$modelName;
        if (! class_exists($modelClass)) {
            $modelClass = Str::beforeLast($modelClass, '\\').'\\'.class_basename($modelClass);
        }

        return $modelClass;
    }

    protected function getModelName(): string
    {
        $modelClass = $this->getModelClass();

        return Str::of(class_basename($modelClass))->snake()->singular();
    }

    protected function getModelPluralName(): string
    {
        $modelClass = $this->getModelClass();

        return Str::of(class_basename($modelClass))->snake()->plural();
    }

    public function index(Request $request)
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass::query();
        if ($request->filled('filter')) {
            $searchableFields = $this->getSearchableFields();
            if (! empty($searchableFields)) {
                $query->where(function ($q) use ($request, $searchableFields) {
                    foreach ($searchableFields as $field) {
                        $q->orWhere($field, 'like', "%{$request->filter}%");
                    }
                });
            }
        }
        if ($request->boolean('active') && method_exists($modelClass, 'scopeOnlyActive')) {
            $query->onlyActive();
        }
        if ($request->boolean('deleted') && method_exists($modelClass, 'scopeOnlyTrashed')) {
            $query->onlyTrashed();
        }
        $sortBy = $request->input('sortBy', 'created_at');
        $direction = $request->input('direction', 'desc');
        $query->orderBy($sortBy, $direction);
        $perPage = $request->input('rowsPerPage', 15);
        $results = $query->paginate($perPage);

        return new ResourceCollection($results);
    }

    public function show($id)
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::findOrFail($id);

        return response()->json($model, 200);
    }

    public function destroy(Request $request, $id)
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::findOrFail($id);
        $force = $request->boolean('force');
        if ($force) {
            $model->forceDelete();
            $message = trans_module('force_destroy', $this->getModelName());
        } else {
            $model->delete();
            $message = trans_module('destroy', $this->getModelName());
        }

        return response()->json(['message' => $message], 200);
    }

    public function destroySelected(Request $request)
    {
        $request->validate(['items' => 'required|array']);
        $modelClass = $this->getModelClass();
        $force = $request->boolean('force');
        $models = $modelClass::whereIn('id', $request->items);
        if ($force) {
            $models->each(function ($model) {
                $model->forceDelete();
            });
            $message = trans_modules('force_destroy', $this->getModelName());
        } else {
            $models->each(function ($model) {
                $model->delete();
            });
            $message = trans_modules('destroy', $this->getModelName());
        }

        return response()->json(['message' => $message], 200);
    }

    public function restore($id)
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json(['message' => trans_module('restore', $this->getModelName())], 200);
    }

    public function restoreSelected(Request $request)
    {
        $request->validate(['items' => 'required|array']);
        $modelClass = $this->getModelClass();
        $modelClass::onlyTrashed()->whereIn('id', $request->items)->each(function ($item) {
            $item->restore();
        });

        return response()->json(['message' => trans_modules('restore', $this->getModelName())], 200);
    }

    protected function getSearchableFields(): array
    {
        $modelClass = $this->getModelClass();
        if (method_exists($modelClass, 'getSearchable')) {
            $model = new $modelClass;
            $fields = $model->getSearchable();

            return is_array($fields) ? array_values(array_filter($fields)) : [];
        }

        return [];
    }
}
