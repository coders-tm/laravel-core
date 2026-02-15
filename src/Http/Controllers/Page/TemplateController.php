<?php

namespace Coderstm\Http\Controllers\Page;

use Coderstm\Http\Controllers\Controller;
use Coderstm\Models\File;
use Coderstm\Models\Page\Template;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TemplateController extends Controller
{
    use \Coderstm\Traits\HasResourceActions;

    public function __construct()
    {
        $this->useModel(Template::class);
    }

    public function index(Request $request)
    {
        $template = Template::query();
        if ($request->filled('filter')) {
            $template->where('name', 'like', "%{$request->filter}%");
        }
        $template = $template->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc');
        if ($request->isNotFilled('rowsPerPage')) {
            return $template->get();
        }

        return new ResourceCollection($template->paginate($request->rowsPerPage ?? 15));
    }

    public function store(Request $request)
    {
        $rules = ['name' => 'required|max:255', 'data' => 'array|required', 'thumbnail' => 'string|nullable'];
        $request->validate($rules);
        if ($request->filled('thumbnail') && strpos($request->thumbnail, 'data:image') === 0) {
            $filename = uniqid('thumbnail_').'.png';
            $imageData = substr($request->thumbnail, strpos($request->thumbnail, ',') + 1);
            Storage::disk('local')->put($filename, base64_decode($imageData));
            $filePath = Storage::disk('local')->path($filename);
            $file = new File;
            $file->setHttpFile(new UploadedFile($filePath, $filename));
            $file->save($request->input());
            unlink($filePath);
            $request->merge(['thumbnail' => $file->url]);
        }
        $template = Template::create($request->input());

        return response()->json(['data' => $template, 'message' => trans_module('store', 'template')], 200);
    }

    public function show($template)
    {
        $template = Template::findOrFail($template);

        return response()->json($template, 200);
    }
}
