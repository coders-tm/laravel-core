<?php

namespace Coderstm\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Qirolab\Theme\Theme as Base;

class Theme extends Base
{
    public static function config(?string $theme = null): ?array
    {
        try {
            $theme = $theme ?? self::active();
            if ($theme) {
                $configPath = self::finder()->getThemePath($theme, 'config.json');
                if (File::exists($configPath)) {
                    return json_decode(file_get_contents($configPath), true);
                }
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function set(string $theme, ?string $parentTheme = null): void
    {
        $config = self::config($theme);
        if (! $parentTheme && isset($config['parent'])) {
            $parentTheme = $config['parent'];
        }
        self::finder()->setActiveTheme($theme, $parentTheme);
        self::loadTranslations($theme);
        Config::set('coderstm.editor.pages_path', self::path('views/pages', $theme));
    }

    protected static function loadTranslations(string $theme): void
    {
        $langPath = self::path('lang', $theme);
        if ($langPath && File::exists($langPath)) {
            $translator = App::make('translator');
            $translator->addPath($langPath);
        }
    }

    public static function path(?string $path = null, ?string $theme = null): ?string
    {
        $theme = $theme ?? self::active();
        $themePath = self::getThemeFilePath($theme, $path);
        if ($themePath) {
            return $themePath;
        }
        $config = self::config($theme);
        if (isset($config['parent'])) {
            return self::getThemeFilePath($config['parent'], $path);
        }

        return null;
    }

    public static function basePath(?string $path = null, ?string $theme = null): ?string
    {
        return self::finder()->getThemePath($theme ?? self::active(), $path);
    }

    public static function publicPath(?string $path = null, ?string $theme = null): ?string
    {
        return self::basePath("public/{$path}", $theme);
    }

    protected static function getThemeFilePath(string $theme, ?string $path = null): ?string
    {
        $themePath = self::finder()->getThemePath($theme, $path);

        return File::exists($themePath) ? $themePath : null;
    }

    public static function mixPath($theme = null)
    {
        $theme = $theme ?? self::active();
        $publicPath = Theme::basePath('.public', $theme);
        if (is_file($publicPath)) {
            $path = rtrim(file_get_contents($publicPath));
            if (! str_starts_with($path, '/')) {
                $path = "/{$path}";
            }

            return $path;
        }

        return "/themes/{$theme}";
    }

    public static function assetsPath(string $themeName, ?string $path = null)
    {
        $mixPath = Theme::mixPath($themeName);
        if (! $path) {
            return public_path($mixPath);
        }
        if (str_starts_with($path, '/')) {
            $path = ltrim($path, '/');
        }

        return public_path("{$mixPath}/{$path}");
    }

    public static function url(string $asset, bool $absolute = true): ?string
    {
        $theme = self::active();
        $mixPath = self::mixPath($theme);
        if (! str_starts_with($asset, '/')) {
            $asset = "/{$asset}";
        }
        $path = $mixPath.$asset;

        return $absolute ? $path : url($path);
    }

    public static function useThemePublic(): bool
    {
        return config('coderstm.theme_public') === true;
    }
}
