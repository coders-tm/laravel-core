<?php

namespace Coderstm\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;

trait HasResourceActions
{
    /**
     * The model class for this resource.
     *
     * Set via property or useModel() method. Falls back to guessing from controller name.
     */
    protected ?string $model = null;

    /**
     * Set the model class for the resource actions.
     *
     * @param  string|object  $model  The model class name or instance
     */
    protected function useModel(string|object $model): void
    {
        $this->model = is_object($model) ? get_class($model) : ltrim($model, '\\');
    }

    /**
     * Get the model class for the resource.
     * Can be set via useModel() or by declaring a $model property.
     */
    protected function getModelClass(): string
    {
        // 1) Prefer model set via useModel() (recommended approach)
        if (! empty($this->model)) {
            return $this->model;
        }

        // 2) Guess model from controller name
        // e.g., BlogController -> Blog, Page\BlockController -> Page\Block
        $controllerClass = get_class($this);
        $modelName = Str::replaceLast('Controller', '', class_basename($controllerClass));

        // Check if controller is in a subdirectory (e.g., Page\BlockController)
        $namespace = Str::beforeLast($controllerClass, '\\');
        $baseNamespace = Str::beforeLast($namespace, '\\');

        // Try to find model in Models namespace
        $modelClass = Str::replaceLast('Controllers', 'Models', $baseNamespace).'\\'.$modelName;

        // If the model doesn't exist, try without the subdirectory
        if (! class_exists($modelClass)) {
            $modelClass = Str::beforeLast($modelClass, '\\').'\\'.class_basename($modelClass);
        }

        return $modelClass;
    }

    /**
     * Get the model name for translations.
     */
    protected function getModelName(): string
    {
        $modelClass = $this->getModelClass();

        return Str::of(class_basename($modelClass))->snake()->singular();
    }

    /**
     * Get the model plural name for translations.
     */
    protected function getModelPluralName(): string
    {
        $modelClass = $this->getModelClass();

        return Str::of(class_basename($modelClass))->snake()->plural();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass::query();

        // Apply filter if provided
        if ($request->filled('filter')) {
            $searchableFields = $this->getSearchableFields();
            // Only apply where conditions if there are searchable fields defined
            if (! empty($searchableFields)) {
                $query->where(function ($q) use ($request, $searchableFields) {
                    foreach ($searchableFields as $field) {
                        $q->orWhere($field, 'like', "%{$request->filter}%");
                    }
                });
            }
        }

        // Apply active filter if the model has is_active
        if ($request->boolean('active') && method_exists($modelClass, 'scopeOnlyActive')) {
            $query->onlyActive();
        }

        // Apply deleted filter if soft deletes are enabled
        if ($request->boolean('deleted') && method_exists($modelClass, 'scopeOnlyTrashed')) {
            $query->onlyTrashed();
        }

        // Apply sorting
        $sortBy = $request->input('sortBy', 'created_at');
        $direction = $request->input('direction', 'desc');
        $query->orderBy($sortBy, $direction);

        // Paginate results
        $perPage = $request->input('rowsPerPage', 15);
        $results = $query->paginate($perPage);

        return new ResourceCollection($results);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::findOrFail($id);

        return response()->json($model, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::findOrFail($id);

        $this->authorize('delete', $model);

        $force = $request->boolean('force');

        if ($force) {
            $model->forceDelete();
            $message = trans_module('force_destroy', $this->getModelName());
        } else {
            $model->delete();
            $message = trans_module('destroy', $this->getModelName());
        }

        return response()->json([
            'message' => $message,
        ], 200);
    }

    /**
     * Remove multiple resources from storage.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
        ]);

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

        return response()->json([
            'message' => $message,
        ], 200);
    }

    /**
     * Restore the specified resource from trash.
     */
    public function restore($id)
    {
        $modelClass = $this->getModelClass();

        $model = $modelClass::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json([
            'message' => trans_module('restore', $this->getModelName()),
        ], 200);
    }

    /**
     * Restore multiple resources from trash.
     */
    public function bulkRestore(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
        ]);

        $modelClass = $this->getModelClass();

        $modelClass::onlyTrashed()
            ->whereIn('id', $request->items)
            ->each(function ($item) {
                $item->restore();
            });

        return response()->json([
            'message' => trans_modules('restore', $this->getModelName()),
        ], 200);
    }

    /**
     * Get the list of resource methods which do not have model parameters.
     *
     * Includes destroy because the trait's method uses $id (untyped),
     * preventing implicit route model binding. When authorizeResource
     * generates can:delete,{parameter}, the parameter is unresolved,
     * causing the gate to receive a raw string instead of a model.
     * Adding destroy here makes authorizeResource use class-level
     * authorization (can:delete,App\Models\X) instead.
     */
    protected function resourceMethodsWithoutModels()
    {
        return ['index', 'create', 'store', 'update', 'destroy'];
    }

    /**
     * Get the searchable fields for the model.
     */
    protected function getSearchableFields(): array
    {
        // Only use model's getSearchable() if available
        $modelClass = $this->getModelClass();
        if (method_exists($modelClass, 'getSearchable')) {
            $model = new $modelClass;
            $fields = $model->getSearchable();

            return is_array($fields) ? array_values(array_filter($fields)) : [];
        }

        // Fallback: no searchable fields defined
        return [];
    }
}
