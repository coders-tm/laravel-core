<?php

namespace Coderstm\Commands;

use Coderstm\Services\Helpers;
use Illuminate\Console\Command;

class BuildTheme extends Command
{
    protected $signature = 'coderstm:theme-build {name} {--path} {--theme-public}';

    protected $description = 'Build a theme using npm run theme:build --name={theme-name}';

    public function handle()
    {
        Helpers::checkNpmInstallation();
        $themeName = $this->argument('name');
        $themePath = $this->option('path');
        $themePublic = $this->option('theme-public') ? 'true' : 'false';
        chdir(base_path());
        $npmBinPath = config('coderstm.npm_bin');
        $npmBuildCommand = "{$npmBinPath}/npm run theme:build --name={$themeName} --path={$themePath} --theme-public={$themePublic}";
        $output = null;
        $resultCode = null;
        exec($npmBuildCommand, $output, $resultCode);
        if (app()->environment('local')) {
            foreach ($output as $line) {
                $this->line($line);
            }
        }
        if ($resultCode === 0) {
            $this->info("Theme '{$themeName}' built successfully with public flag: {$themePublic}!");
        } else {
            $this->error("Failed to build the theme '{$themeName}'. Please check the npm output.");
        }

        return $resultCode;
    }
}
