<?php

namespace Coderstm\Http\Controllers\Page;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Coderstm\Http\Controllers\Controller;
use Coderstm\Services\Theme;

class BlockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get all .blade.php files from the specified directory
        $files = File::allFiles($this->path());

        $blocks = [];

        foreach ($files as $file) {
            // Extract the file name without the extension
            $filename = Str::replaceLast('.blade.php', '', $file->getFilename());

            // Read the file content
            $content = File::get($file->getPathname());

            // Parse the comment to get the 'Name' label (e.g., {{-- Name: Example --}})
            preg_match('/\{\{-- Name:\s*(.*)\s*--\}\}/i', $content, $matches);
            $label = $matches[1] ?? Str::title($filename);

            // Render the Blade view file using `view()` to get the actual content
            $viewContent = view("blocks.{$filename}")->withoutShortcodes();

            // Create the array structure
            $blocks[] = [
                'id' => $filename,
                'label' => trim($label), // Trim any extra whitespace from label
                'content' => "$viewContent", // Rendered content as a string
                'attributes' => [
                    'class' => 'fa fa-cube',
                ]
            ];
        }

        return response()->json($blocks, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate those rules
        $request->validate([
            'id' => 'string|required',
            'label' => 'string|required',
            'content' => 'string|required',
        ]);

        // Convert the label to a slug for the filename
        $label = $request->label;
        $content = $request->content;
        $name = Str::slug($label);

        try {
            // Define the path where the Blade file will be stored
            $filePath = $this->path("/{$name}.blade.php");

            File::ensureDirectoryExists(dirname($filePath));

            // Step 6: Store the Blade file in the blocks directory
            File::put($filePath, "{{-- Name: {$label} --}}\n\n{$content}");
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json([
            'data' => $request->input(),
            'message' => trans_module('store', 'block'),
        ], 200);
    }

    private function path($path = null): string
    {
        $path = 'views/blocks' . $path;

        if ($theme = Theme::active()) {
            return Theme::basePath($path, $theme);
        }

        return resource_path($path);
    }
}
