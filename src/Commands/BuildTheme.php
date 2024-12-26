<?php

namespace Coderstm\Commands;

use Coderstm\Services\Helpers;
use Illuminate\Console\Command;

class BuildTheme extends Command
{
    // The name and signature of the console command
    protected $signature = 'coderstm:theme-build {name} {--path} {--theme-public}';

    // The console command description
    protected $description = 'Build a theme using npm run theme:build --name={theme-name}';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Check if npm is installed and the test command can be run
        Helpers::checkNpmInstallation();

        // Get the theme name from the command argument
        $themeName = $this->argument('name');

        // Check if the theme-public option is provided
        $themePath = $this->option('path');
        $themePublic = $this->option('theme-public') ? 'true' : 'false';

        // Change the current working directory to the base path of the application
        chdir(base_path());

        // Retrieve the npm binary path from the configuration
        $npmBinPath = config('coderstm.npm_bin');

        // Build the npm command with optional theme-public flag
        $npmBuildCommand = "{$npmBinPath}/npm run theme:build --name={$themeName} --path={$themePath} --theme-public={$themePublic}";

        $output = null;
        $resultCode = null;

        // Execute the npm build command
        exec($npmBuildCommand, $output, $resultCode);

        if (app()->environment('local')) {
            // Output npm response
            foreach ($output as $line) {
                $this->line($line);
            }
        }

        // Check if the command was successful
        if ($resultCode === 0) {
            $this->info("Theme '{$themeName}' built successfully with public flag: {$themePublic}!");
        } else {
            $this->error("Failed to build the theme '{$themeName}'. Please check the npm output.");
        }

        return $resultCode;
    }
}
