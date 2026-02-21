<?php

namespace Coderstm\Services;

use Coderstm\Http\Controllers\WebPageController;
use Illuminate\Support\Facades\Route;

class PageService
{
    public function routes()
    {
        if (app()->routesAreCached()) {
            return;
        }
        $registryPath = config('coderstm.editor.registry_path');
        if (! file_exists($registryPath)) {
            return;
        }
        $pages = json_decode(file_get_contents($registryPath), true) ?: [];
        foreach ($pages as $page) {
            $slug = $page['slug'];
            $parent = $page['parent'] ?? null;
            $path = $parent ? "{$parent}/{$slug}" : $slug;
            if ($path) {
                Route::get($path, [WebPageController::class, 'pages'])->defaults('slug', $page['slug'])->name('pages.'.$slug);
            }
        }
    }
}
