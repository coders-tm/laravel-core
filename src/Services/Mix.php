<?php

namespace Coderstm\Services;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Mix
{
    public function __invoke($path, $themeName)
    {
        static $manifests = [];
        if (! str_starts_with($path, '/')) {
            $path = "/{$path}";
        }
        if (is_file(self::assetsPath('hot', $themeName))) {
            $url = rtrim(file_get_contents(self::assetsPath('hot', $themeName)));
            $customUrl = app('config')->get('app.mix_hot_proxy_url');
            if (! empty($customUrl)) {
                return new HtmlString("{$customUrl}{$path}");
            }
            if (Str::startsWith($url, ['http://', 'https://'])) {
                return new HtmlString(Str::after($url, ':').$path);
            }

            return new HtmlString("//localhost:8080{$path}");
        }
        $manifestPath = static::assetsPath('mix-manifest.json', $themeName);
        if (! isset($manifests[$manifestPath]) && is_file($manifestPath)) {
            $manifests[$manifestPath] = json_decode(file_get_contents($manifestPath), true);
        }
        $manifest = $manifests[$manifestPath] ?? [];
        $themeFile = Theme::url($manifest[$path] ?? $path);

        return new HtmlString($themeFile);
    }

    private static function assetsPath(string $path, string $themeName)
    {
        $mixPath = Theme::mixPath($themeName);

        return public_path("{$mixPath}/{$path}");
    }
}
