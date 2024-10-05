<?php

namespace Coderstm\Services;

use Coderstm\Services\Theme;
use Exception;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Mix
{
    public static $publicPath = true;

    /**
     * Get the path to a versioned Mix file.
     *
     * @param  string  $path
     * @param  string  $themeName
     * @return \Illuminate\Support\HtmlString|string
     *
     * @throws \Exception
     */
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
                return new HtmlString(Str::after($url, ':') . $path);
            }

            return new HtmlString("//localhost:8080{$path}");
        }

        $manifestPath = static::assetsPath('mix-manifest.json', $themeName);

        if (! isset($manifests[$manifestPath])) {
            if (! is_file($manifestPath)) {
                throw new Exception("Mix manifest not found at: {$manifestPath}");
            }

            $manifests[$manifestPath] = json_decode(file_get_contents($manifestPath), true);
        }

        $manifest = $manifests[$manifestPath];

        $themeFile = Theme::url($manifest[$path] ?? $path);

        return new HtmlString($themeFile);
    }

    private static function assetsPath(string $path, string $themeName)
    {
        if (static::$publicPath) {
            return public_path("themes/$themeName/$path");
        }

        return Theme::publicPath(...func_get_args());
    }
}
