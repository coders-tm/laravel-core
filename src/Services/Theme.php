<?php

namespace Coderstm\Services;

use Illuminate\Support\Facades\File;
use Qirolab\Theme\Theme as Base;

class Theme extends Base
{
    /**
     * Get the config of the theme.
     *
     * @param string|null $theme
     * @return array|null
     */
    public static function config(string $theme = null): ?array
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
        } catch (\Exception $e) {
            // You could log the exception here for debugging purposes
            return null;
        }
    }

    /**
     * Set the active theme and optionally its parent theme.
     *
     * @param string $theme
     * @param string|null $parentTheme
     * @return void
     */
    public static function set(string $theme, string $parentTheme = null): void
    {
        $config = self::config($theme);

        if (!$parentTheme && isset($config['parent'])) {
            $parentTheme = $config['parent'];
        }

        self::finder()->setActiveTheme($theme, $parentTheme);
    }

    /**
     * Get the path of a specific file within the theme or its parent theme.
     *
     * @param string|null $path
     * @param string|null $theme
     * @return string|null
     */
    public static function path(string $path = null, string $theme = null): ?string
    {
        $theme = $theme ?? self::active();

        // Try to get the file path from the current theme
        $themePath = self::getThemeFilePath($theme, $path);

        if ($themePath) {
            return $themePath;
        }

        // Fallback to the parent theme if available
        $config = self::config($theme);

        if (isset($config['parent'])) {
            return self::getThemeFilePath($config['parent'], $path);
        }

        return null;
    }

    /**
     * Get the base path of a specific file within the theme or its parent theme.
     *
     * @param string|null $path
     * @param string|null $theme
     * @return string|null
     */
    public static function basePath(string $path = null, string $theme = null): ?string
    {
        return self::finder()->getThemePath($theme ?? self::active(), $path);
    }

    /**
     * Get the public path of a specific file within the theme or its parent theme.
     *
     * @param string|null $path
     * @param string|null $theme
     * @return string|null
     */
    public static function publicPath(string $path = null, string $theme = null): ?string
    {
        return self::basePath("public/$path", $theme);
    }

    /**
     * Helper method to get the file path from a specific theme.
     *
     * @param string $theme
     * @param string|null $path
     * @return string|null
     */
    protected static function getThemeFilePath(string $theme, string $path = null): ?string
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

        return "/themes/$theme";
    }

    public static function assetsPath(string $themeName, string $path = null)
    {
        $mixPath = Theme::mixPath($themeName);

        if (! $path) {
            return public_path($mixPath);
        }

        if (str_starts_with($path, '/')) {
            $path = ltrim($path, '/');
        }

        return public_path("$mixPath/$path");
    }

    /**
     * Get theme's asset url.
     */
    public static function url(string $asset, bool $absolute = true): ?string
    {
        $theme = self::active();
        $mixPath = self::mixPath($theme);

        if (! str_starts_with($asset, '/')) {
            $asset = "/{$asset}";
        }

        $path = $mixPath . $asset;

        return $absolute ? $path : url($path);
    }

    public static function useThemePublic(): bool
    {
        return config('coderstm.theme_public') === true;
    }
}
