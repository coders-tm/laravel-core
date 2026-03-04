<?php

namespace Coderstm\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ThemeLink extends Command
{
    protected $signature = 'theme:link
                            {--force : Overwrite the assets symlink if it already exists}';

    protected $description = 'Link the theme, ensuring assets are correctly linked.';

    protected $force = false;

    public function handle()
    {
        $themes = base_path('themes');
        File::ensureDirectoryExists(public_path('themes'));
        foreach (File::directories($themes) as $theme) {
            $name = basename($theme);
            $mixPath = \Coderstm\Services\Theme::mixPath($name);
            $source = base_path("themes/{$name}/assets");
            $destination = public_path(ltrim($mixPath, '/'));
            File::ensureDirectoryExists(dirname($destination));
            if (! File::exists($destination) || $this->force) {
                if (File::exists($destination)) {
                    File::delete($destination);
                }
                symlink($source, $destination);
                $this->info("Linked theme assets: {$name} -> {$mixPath}");
            }
        }
    }
}
