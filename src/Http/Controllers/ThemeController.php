<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Models\AppSetting;
use Illuminate\Support\Str;
use Coderstm\Services\Theme;
use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

class ThemeController extends Controller
{
    // List all themes
    public function index()
    {
        $themesPath = config('theme.base_path');
        $themeFolders = File::directories($themesPath);

        $themes = array_map(function ($folder) {
            $configFile = $folder . '/config.json';
            if (File::exists($configFile)) {
                $config = json_decode(File::get($configFile), true);
                $config['key'] = basename($folder); // Add theme path to the response
                $config['active'] = Theme::active() == $config['key']; // Add theme active to the response
                $config['modified_at'] = date('Y-m-d H:i:s', filemtime($folder));
                $config['preview'] = route('home', ['theme' => $config['key']]);
                return $config;
            }
            return null;
        }, $themeFolders);

        return response()->json(array_filter($themes), 200);
    }

    // Activate a theme
    public function activate($theme)
    {
        $configPath = Theme::basePath('config.json', $theme);

        if (File::exists($configPath)) {
            Theme::set($theme);
            AppSetting::updateValue('theme', ['active' => $theme]);
            return response()->json(['message' => 'Theme activated successfully'], 200);
        }

        return response()->json(['message' => 'Theme not found!'], 404);
    }

    // Delete a theme
    public function destroy($theme)
    {
        $defaultTheme = 'foundation';
        $activeTheme = Theme::active();

        // Prevent deletion of default or active theme
        if ($theme === $defaultTheme || $theme === $activeTheme) {
            return response()->json(['message' => 'Cannot delete the default or active theme!'], 403);
        }

        $themePath = Theme::basePath('', $theme);
        if (File::exists($themePath)) {
            File::deleteDirectory($themePath);
            return response()->json(['message' => 'Theme deleted successfully!'], 200);
        }

        return response()->json(['message' => 'Theme not found!'], 404);
    }

    // Clone a theme
    public function clone($theme)
    {
        $config = Theme::config($theme);
        $newThemeName = $config['name'] . ' (Copy)';
        $newThemeKey = Str::slug($newThemeName);

        $themePath = Theme::basePath('', $theme);
        $newThemePath = Theme::basePath('', $newThemeKey);

        if (File::exists($themePath) && !File::exists($newThemePath)) {
            // Clone theme directory
            File::copyDirectory($themePath, $newThemePath);

            // Update the config.json of the cloned theme
            $config = Theme::config($newThemeKey);
            $config['name'] = $newThemeName;
            $config['parent'] = $theme;

            File::put(Theme::basePath('config.json', $newThemeKey), json_encode($config, JSON_PRETTY_PRINT));

            return response()->json(['message' => 'Theme cloned successfully'], 200);
        }

        return response()->json(['message' => 'Theme not found or new theme already exists'], 404);
    }

    // Get the list of files and directories in a theme with `basepath` for the editor
    public function getFiles($theme)
    {
        $themePath = Theme::basePath('views', $theme);

        if (!File::exists($themePath)) {
            return response()->json(['message' => 'Theme not found'], 404);
        }

        $fileTree = $this->getDirectoryStructure($themePath, $themePath);
        $themeInfo = $this->info($theme);

        return response()->json([
            'files' => $fileTree,
            'info' => $themeInfo
        ], 200);
    }

    // Recursive function to build file and folder structure
    private function getDirectoryStructure($directory, $basepath)
    {
        $items = [];

        // Get all directories and files in the current directory
        $directories = File::directories($directory);
        $files = File::files($directory);

        // Add directories to the structure
        foreach ($directories as $dir) {
            $dirName = basename($dir);

            // Exclude the public directory
            if (!in_array($dirName, ['public'])) {
                // Recursively get directory structure while skipping 'public' and its children
                $singular = Str::singular($dirName);
                $items[] = [
                    'name' => $dirName,
                    'ext' => '.blade.php',
                    'addLabel' => "Add a new $singular",
                    'basepath' => str_replace($basepath . '/', '', $dir),
                    'header' => 'directory',
                    'modified_at' => date('Y-m-d H:i:s', filemtime($dir)),
                    'children' => $this->getDirectoryStructure($dir, $basepath)
                ];
            }
        }

        // Add files to the structure
        foreach ($files as $file) {
            $fileName = basename($file->getPathname());

            // Exclude the 'preview.png' file
            if ($fileName === 'preview.png') {
                continue; // Skip 'preview.png'
            }

            $items[] = [
                'name' => $fileName,
                'basepath' => str_replace($basepath . '/', '', $file->getPathname()),
                'icon' => 'fas fa-code',
                'modified_at' => date('Y-m-d H:i:s', filemtime($file)),
                'header' => 'file'
            ];
        }

        return $items;
    }

    public function getFileContent($theme, Request $request)
    {
        $filePath = $request->input('key');  // The relative path of the selected file
        $fullPath = realpath(Theme::basePath("views/$filePath", $theme));

        if (!$fullPath || !File::exists($fullPath)) {
            return response()->json(['message' => 'File not found or invalid path'], 404);
        }

        // Read the file content
        $content = File::get($fullPath);

        return response()->json([
            'file' => $filePath,
            'content' => $content,
        ], 200);
    }

    // Save edited file content
    public function saveFile(Request $request, $theme)
    {
        $filePath = $request->input('key');
        $content = $request->input('content');
        $themePath = Theme::basePath("views/$filePath", $theme);

        // Validate Blade syntax (if it's a .blade.php file)
        if (File::extension($filePath) === 'php') {
            try {
                Blade::compileString($content);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Syntax error: ' . $e->getMessage()], 400);
            }
        }

        // Save file content
        File::put($themePath, $content);

        return response()->json(['message' => 'File saved successfully'], 200);
    }

    public function createFile(Request $request, $theme)
    {
        $request->validate([
            'name' => 'required|string',
            'ext' => 'required|string',
            'basepath' => 'required|string',
            'template' => 'nullable|string', // Template is optional
        ]);

        $fileName = $request->input('name') . $request->ext;
        $basepath = rtrim($request->input('basepath'), '/');
        $themePath = Theme::basePath("views/$basepath", $theme);


        if (!File::exists($themePath)) {
            return response()->json(['message' => 'Theme not found'], 404);
        }

        $filePath = Theme::basePath("views/$basepath/$fileName", $theme);

        // Check if the file already exists
        if (File::exists($filePath)) {
            return response()->json(['message' => 'File already exists'], 400);
        }

        // If a template is provided, copy the content of the template file
        if ($request->filled('template')) {
            $templatePath = $themePath . '/' . $request->template;

            // Check if the template file exists
            if (!File::exists($templatePath)) {
                return response()->json(['message' => 'Template file not found'], 404);
            }

            // Create the new file with the content of the template
            $templateContent = File::get($templatePath);
            File::put($filePath, $templateContent);
        } else {
            // Create an empty file if no template is provided
            File::put($filePath, '');
        }

        return response()->json([
            'message' => 'File created successfully',
            'file' => [
                'name' => $fileName,
                'basepath' => str_replace(Theme::basePath('views', $theme) . '/', '', $filePath),
                'icon' => 'fas fa-code',
                'header' => 'file'
            ]
        ], 201);
    }

    public function destroyThemeFile(Request $request, $theme)
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $filePath = $request->input('key');  // The relative path of the selected file
        $fullPath = realpath(Theme::basePath('views/' . $filePath, $theme));

        // Check if the file exists
        if (!$fullPath || !File::exists($fullPath)) {
            return response()->json(['message' => 'File not found or invalid path'], 404);
        }

        // Prevent deletion of specific directories or files (like 'public' or 'preview.png')
        if (str_contains($filePath, 'public') || str_contains($filePath, 'preview.png')) {
            return response()->json(['message' => 'This file or directory cannot be deleted'], 403);
        }

        // Attempt to delete the file
        try {
            File::delete($fullPath);
            return response()->json(['message' => 'File deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error deleting file', 'error' => $e->getMessage()], 500);
        }
    }

    // Show selected theme details
    protected function info($theme)
    {
        $folder = Theme::basePath('', $theme);
        $configFile = Theme::basePath('config.json', $theme);

        if (File::exists($configFile)) {
            $config = json_decode(File::get($configFile), true);
            $config['key'] = $theme; // Add theme path to the response
            $config['active'] = Theme::active() == $theme; // Add theme active to the response
            $config['modified_at'] = date('Y-m-d H:i:s', filemtime($folder));
            $config['preview'] = route('home', ['theme' => $theme]);

            return $config;
        }

        return null;
    }

    public function preview($theme)
    {
        // Check if the preview image is cached
        $cacheKey = 'theme_preview_' . $theme;
        if (Cache::has($cacheKey)) {
            $cachedImagePath = Cache::get($cacheKey);
            return Response::file($cachedImagePath);
        }

        // Generate the preview image
        $imagePath = Theme::basePath('preview.png', $theme);

        try {
            // Capture homepage as a PNG using Browsershot
            Browsershot::url(route('home', ['theme' => $theme]))
                ->setScreenshotType('jpeg', 50)
                ->windowSize(1440, 792)
                ->save($imagePath);

            // Cache the image path for 12 hours
            Cache::put($cacheKey, $imagePath, 180 * 60 * 4); // Cache for 12 hours
        } catch (\Exception $e) {
            return response('Error generating preview', 404);
        }

        // Return the generated image
        return Response::file($imagePath);
    }
}
