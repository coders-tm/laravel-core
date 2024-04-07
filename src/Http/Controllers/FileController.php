<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Models\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Coderstm\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, File $file)
    {
        $file = $file->query();

        if ($request->filled('filter')) {
            $file->where('original_file_name', 'like', "%{$request->filter}%");
        }

        if ($request->filled('type')) {
            $file->where('mime_type', 'like', "%{$request->type}%");
        }

        if ($request->input('deleted') ? $request->boolean('deleted') : false) {
            $file->onlyTrashed();
        }

        $file = $file->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc')
            ->paginate($request->rowsPerPage ?? 15);
        return new ResourceCollection($file);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'media' => 'required',
        ];

        $this->validate($request, $rules);

        $file = new File;
        $file->setHttpFile($request->file('media'));
        $file->save();
        return response()->json(new JsonResource($file->fresh()), 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Coderstm\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function show(File $file)
    {
        return response()->json(new JsonResource($file), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Coderstm\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, File $file)
    {
        $rules = [
            'media' => 'required',
        ];

        $this->validate($request, $rules);

        $file->setHttpFile($request->file('media'));
        $file->modify();

        return response()->json(new JsonResource($file->fresh()), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Coderstm\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function destroy(File $file)
    {
        // Storage::disk($file->disk)->delete($file->path);
        $file = $file->delete();
        return response()->json($file, 200);
    }

    /**
     * Remove the selected resource from storage.
     *
     * @param  \Coderstm\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function destroySelected(Request $request, File $file)
    {
        $this->validate($request, [
            'items' => 'required',
        ]);
        $file->whereIn('id', $request->items)->each(function ($item) {
            $item->delete();
        });
        return response()->json([
            'message' => trans_choice('coderstm::messages.files.destroy', 2),
        ], 200);
    }

    /**
     * Restore the specified resource from storage.
     *
     * @param  \Coderstm\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        File::onlyTrashed()
            ->where('id', $id)->each(function ($item) {
                $item->restore();
            });
        return response()->json([
            'message' => trans_choice('coderstm::messages.files.restored', 1),
        ], 200);
    }

    /**
     * Remove the selected resource from storage.
     *
     * @param  \Coderstm\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function restoreSelected(Request $request, File $file)
    {
        $this->validate($request, [
            'items' => 'required',
        ]);
        $file->onlyTrashed()
            ->whereIn('id', $request->items)->each(function ($item) {
                $item->restore();
            });
        return response()->json([
            'message' => trans_choice('coderstm::messages.files.restored', 2),
        ], 200);
    }

    /**
     * Download the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function download(Request $request)
    {
        try {
            $file = File::findByHash($request->hash ?? '');

            if ($request->has('download')) {
                return Storage::disk($file->disk)->download($file->path, $file->original_file_name);
            } else if ($file->disk == 'cloud') {
                return response()->redirectTo(Storage::disk($file->disk)->url($file->path));
            }

            return response()->file(Storage::disk($file->disk)->path($file->path));
        } catch (\Throwable $th) {
            return abort(404, trans('coderstm::messages.files.not_found'));
        }
    }

    public function uploadFromSource(Request $request)
    {
        $rules = [
            'source' => 'required|url',
        ];

        $this->validate($request, $rules);

        $url = $request->input('source');
        $_path = parse_url($url)['path'];
        $paths = explode("/", $_path);
        $name = $paths[count($paths) - 1];
        try {
            $path = "files/" . md5($url) . ".png";
            $media = Http::get($url);
            Storage::disk('public')->put($path, $media);

            $filePath = Storage::disk('public')->path($path);

            $file = new File();
            $file->setHttpFile(new UploadedFile($filePath, $name));
            $file->save($request->input());

            // delete file
            unlink($filePath);

            return response()->json(new JsonResource($file->fresh()), 200);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
