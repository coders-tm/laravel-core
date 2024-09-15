<?php

namespace Coderstm\Http\Controllers\Page;

use Coderstm\Models\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Coderstm\Models\Page\Template;
use Illuminate\Support\Facades\Storage;
use Coderstm\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TemplateController extends Controller
{
    /**
     * Create the controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->authorizeResource(Template::class);
    }

    /**
     * Display a listing of the resource.
     */
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Set rules
        $rules = [
            'name' => 'required|max:255',
            'data' => 'array|required',
            'thumbnail' => 'string|nullable',
        ];

        // Validate those rules
        $request->validate($rules);

        // Check if thumbnail is a data image URL
        if ($request->filled('thumbnail') && strpos($request->thumbnail, 'data:image') === 0) {
            // Generate a unique filename for the thumbnail
            $filename = uniqid('thumbnail_') . '.png';

            // Decode the data URL and save the image to storage
            $imageData = substr($request->thumbnail, strpos($request->thumbnail, ',') + 1);
            Storage::disk('local')->put($filename, base64_decode($imageData));

            $filePath = Storage::disk('local')->path($filename);

            $file = new File();
            $file->setHttpFile(new UploadedFile($filePath, $filename));
            $file->save($request->input());

            // delete file
            unlink($filePath);

            // Update the thumbnail field with the path to the stored image
            $request->merge(['thumbnail' => $file->url]);
        }

        // create the template
        $template = Template::create($request->input());

        return response()->json([
            'data' => $template,
            'message' => trans_module('store', 'template'),
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Template $template)
    {
        return response()->json($template, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Template $template)
    {
        $template->delete();

        return response()->json([
            'message' => trans_module('destroy', 'template'),
        ], 200);
    }
}
