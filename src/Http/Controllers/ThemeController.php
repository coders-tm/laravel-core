<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Models\AppSetting;
use Coderstm\Services\Helpers;
use Coderstm\Services\Theme;
use Coderstm\Services\Theme\FileMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ThemeController extends Controller
{
    protected $basePath = null;

    public $homeRoute = 'home';

    public function index()
    {
        $themesPath = config('theme.base_path');
        $themeFolders = File::directories($themesPath);
        $themes = array_map(function ($folder) {
            $configFile = $folder.'/config.json';
            if (File::exists($configFile)) {
                $config = json_decode(File::get($configFile), true);
                $config['key'] = basename($folder);
                $config['active'] = Theme::active() == $config['key'];
                $config['modified_at'] = date('Y-m-d H:i:s', filemtime($folder));
                $config['preview'] = route($this->homeRoute, ['theme' => $config['key']]);

                return $config;
            }

            return null;
        }, $themeFolders);

        return response()->json(array_filter($themes), 200);
    }

    public function activate($theme)
    {
        $configPath = Theme::basePath('config.json', $theme);
        if (File::exists($configPath)) {
            Theme::set($theme);
            AppSetting::updateValue('theme', ['active' => $theme]);

            return response()->json(['message' => __('Theme activated successfully')], 200);
        }

        return response()->json(['message' => __('Theme not found!')], 404);
    }

    public function destroy($theme)
    {
        $defaultTheme = 'foundation';
        $activeTheme = Theme::active();
        if ($theme === $defaultTheme || $theme === $activeTheme) {
            return response()->json(['message' => __('Cannot delete the default or active theme!')], 403);
        }
        $themePath = Theme::basePath('', $theme);
        if (File::exists($themePath)) {
            File::deleteDirectory($themePath);

            return response()->json(['message' => __('Theme deleted successfully!')], 200);
        }

        return response()->json(['message' => __('Theme not found!')], 404);
    }

    public function clone($theme)
    {
        $config = Theme::config($theme);
        $newThemeName = $config['name'].' (Copy)';
        $newThemeKey = Str::slug($theme.'-'.now()->timestamp);
        $themePath = Theme::basePath('', $theme);
        $newThemePath = Theme::basePath('', $newThemeKey);
        $themeMixPath = Theme::assetsPath($theme);
        $newThemeMixPath = Theme::assetsPath($newThemeKey);
        if (File::exists($themePath) && ! File::exists($newThemePath)) {
            File::copyDirectory($themePath, $newThemePath);
            File::copyDirectory($themeMixPath, $newThemeMixPath);
            $config = Theme::config($newThemeKey);
            $config['name'] = $newThemeName;
            $config['parent'] = $theme;
            File::put(Theme::basePath('config.json', $newThemeKey), json_encode($config, JSON_PRETTY_PRINT));

            return response()->json(['message' => __('Theme cloned successfully, theme build queued.')], 200);
        }

        return response()->json(['message' => __('Theme not found or new theme already exists')], 404);
    }

    public function getFiles($theme)
    {
        $themePath = Theme::basePath($this->basePath, $theme);
        $assetsPath = Theme::assetsPath($theme);
        if (! File::exists($themePath)) {
            return response()->json(['message' => __('Theme not found')], 404);
        }
        $fileTree = $this->getDirectoryStructure($themePath);
        $themeInfo = $this->info($theme);
        $assetsTree = $this->getDirectoryStructure($assetsPath, 'assets');
        $fileTree = collect($fileTree)->map(function ($item) use ($assetsTree) {
            if (isset($item['basepath']) && Str::startsWith($item['basepath'], 'assets')) {
                $item['children'] = $assetsTree;
            }

            return $item;
        });

        return response()->json(['files' => $fileTree->values(), 'info' => $themeInfo], 200);
    }

    private function getDirectoryStructure($directory, $prefix = null, $basepath = null)
    {
        $items = [];
        $basepath = $basepath ?? $directory;
        $directories = File::directories($directory);
        $files = File::files($directory);
        foreach ($directories as $dir) {
            $dirName = basename($dir);
            $relativePath = str_replace($basepath.'/', '', $dir);
            $relativePath = $prefix ? $prefix.'/'.$relativePath : $relativePath;
            if (! in_array($dirName, ['public'])) {
                $singular = Helpers::singularizeDirectoryName($dirName);
                $assets = $dirName === 'assets';
                $items[] = ['name' => $dirName, 'addLabel' => "Add a new {$singular}", 'basepath' => $relativePath, 'header' => 'directory', 'modified_at' => date('Y-m-d H:i:s', filemtime($dir)), 'children' => $assets ? [] : $this->getDirectoryStructure($dir, $prefix, $basepath)];
            }
        }
        foreach ($files as $file) {
            $items[] = (new FileMeta($file, $basepath, $prefix))->toArray();
        }

        return $items;
    }

    public function getFileContent($theme, Request $request)
    {
        $filePath = $request->input('key');
        if (Str::startsWith($filePath, 'assets')) {
            $filePath = str_replace('assets/', '', $filePath);
            $fullPath = Theme::assetsPath($theme, $filePath);
        } else {
            $fullPath = Theme::basePath("{$this->basePath}/{$filePath}", $theme);
        }
        $fullPath = realpath($fullPath);
        if (! $fullPath || ! File::exists($fullPath)) {
            return response()->json(['message' => __('File not found or invalid path')], 404);
        }
        $content = File::get($fullPath);

        return response()->json(['file' => $filePath, 'content' => $content], 200);
    }

    public function saveFile(Request $request, $theme)
    {
        $filePath = $request->input('key');
        $content = $request->input('content');
        if (Str::startsWith($filePath, 'assets')) {
            $filePath = str_replace('assets/', '', $filePath);
            $fullPath = Theme::assetsPath($theme, $filePath);
        } else {
            $fullPath = Theme::basePath("{$this->basePath}/{$filePath}", $theme);
        }
        $themePath = realpath($fullPath);
        if (File::extension($filePath) === 'php') {
            try {
                Blade::compileString($content);
            } catch (\Throwable $e) {
                return response()->json(['message' => __('Security or syntax error').': '.$e->getMessage()], 400);
            }
        }
        File::put($themePath, $content);

        return response()->json(['message' => __('File saved successfully')], 200);
    }

    public function createFile(Request $request, $theme)
    {
        $request->validate(['name' => 'required|string', 'ext' => 'required|string', 'basepath' => 'required|string', 'template' => 'nullable|string']);
        $fileName = $request->input('name').$request->ext;
        $basepath = rtrim($request->input('basepath'), '/');
        $themePath = Theme::basePath("{$this->basePath}/{$basepath}", $theme);
        if (! File::exists($themePath)) {
            return response()->json(['message' => __('Theme not found')], 404);
        }
        $filePath = Theme::basePath("{$this->basePath}/{$basepath}/{$fileName}", $theme);
        if (File::exists($filePath)) {
            return response()->json(['message' => __('File already exists')], 400);
        }
        if ($request->filled('template')) {
            $templatePath = $themePath.'/'.$request->template;
            if (! File::exists($templatePath)) {
                return response()->json(['message' => __('Template file not found')], 404);
            }
            $templateContent = File::get($templatePath);
            File::put($filePath, $templateContent);
        } else {
            File::put($filePath, '');
        }

        return response()->json(['message' => __('File created successfully'), 'file' => ['name' => $fileName, 'basepath' => str_replace(Theme::basePath($this->basePath, $theme).'/', '', $filePath), 'icon' => 'fas fa-code', 'header' => 'file']], 201);
    }

    public function destroyThemeFile(Request $request, $theme)
    {
        $request->validate(['key' => 'required|string']);
        $filePath = $request->input('key');
        $fullPath = realpath(Theme::basePath($this->basePath.'/'.$filePath, $theme));
        if (! $fullPath || ! File::exists($fullPath)) {
            return response()->json(['message' => __('File not found or invalid path')], 404);
        }
        if (str_contains($filePath, 'public') || str_contains($filePath, 'preview.png') || str_contains($filePath, 'config.json')) {
            return response()->json(['message' => __('This file or directory cannot be deleted')], 403);
        }
        try {
            File::delete($fullPath);

            return response()->json(['message' => __('File deleted successfully')], 200);
        } catch (\Throwable $e) {
            return response()->json(['message' => __('Error deleting file'), 'error' => $e->getMessage()], 500);
        }
    }

    public function assetsUpload(Request $request, $theme)
    {
        $request->validate(['media' => ['required', 'mimetypes:image/jpeg,image/png,image/gif', 'max:300']], ['media.required' => 'Please select an image to upload.', 'media.mimetypes' => 'The file must be an image (JPEG, PNG, GIF).', 'media.max' => 'The file must not be larger than 300 KB.']);
        $fileName = $request->file('media')->getClientOriginalName();
        $filePath = Theme::basePath("{$this->basePath}assets/img/{$fileName}", $theme);
        if (File::exists($filePath)) {
            return response()->json(['message' => __('File already exists')], 422);
        }
        $request->file('media')->move(dirname($filePath), $fileName);

        return response()->json(['name' => $fileName, 'basepath' => str_replace(Theme::basePath($this->basePath, $theme).'/', '', $filePath), 'icon' => 'fas fa-image', 'header' => 'file'], 201);
    }

    public function assets(Request $request, $theme)
    {
        $path = str_replace(['..', './', '\\'], '', $request->input('path'));
        if (Str::endsWith($path, '.php')) {
            abort(404);
        }
        $filePath = Theme::basePath($path, $theme);
        $realFilePath = realpath($filePath);
        if ($realFilePath && File::exists($realFilePath)) {
            return response()->file($realFilePath);
        }
        abort(404);
    }

    protected function info($theme)
    {
        $folder = Theme::basePath('', $theme);
        $configFile = Theme::basePath('config.json', $theme);
        if (File::exists($configFile)) {
            $config = json_decode(File::get($configFile), true);
            $config['key'] = $theme;
            $config['active'] = Theme::active() == $theme;
            $config['modified_at'] = date('Y-m-d H:i:s', filemtime($folder));
            $config['preview'] = route($this->homeRoute, ['theme' => $theme]);

            return $config;
        }

        return null;
    }
}
