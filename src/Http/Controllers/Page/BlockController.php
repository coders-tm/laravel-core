<?php

namespace Coderstm\Http\Controllers\Page;

use Coderstm\Http\Controllers\Controller;
use Coderstm\Services\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BlockController extends Controller
{
    public function index(Request $request)
    {
        $files = File::allFiles($this->path());
        $blocks = [];
        foreach ($files as $file) {
            $filename = Str::replaceLast('.blade.php', '', $file->getFilename());
            $content = File::get($file->getPathname());
            preg_match('/\\{\\{-- Name:\\s*(.*)\\s*--\\}\\}/i', $content, $matches);
            $label = $matches[1] ?? Str::title($filename);
            $viewContent = view("blocks.{$filename}")->withoutShortcodes();
            $blocks[] = ['id' => $filename, 'label' => trim($label), 'content' => "{$viewContent}", 'attributes' => ['class' => 'fa fa-cube']];
        }

        return response()->json($blocks, 200);
    }

    public function store(Request $request)
    {
        $request->validate(['id' => 'string|required', 'label' => 'string|required', 'content' => 'string|required']);
        $label = $request->label;
        $content = $request->content;
        $name = Str::slug($label);
        try {
            $filePath = $this->path("/{$name}.blade.php");
            File::ensureDirectoryExists(dirname($filePath));
            File::put($filePath, "{{-- Name: {$label} --}}\n\n{$content}");
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json(['data' => $request->input(), 'message' => trans_module('store', 'block')], 200);
    }

    private function path($path = null): string
    {
        $path = 'views/blocks'.$path;
        if ($theme = Theme::active()) {
            return Theme::basePath($path, $theme);
        }

        return resource_path($path);
    }
}
