<?php

namespace Coderstm\Commands;

use Coderstm\Services\Helpers;
use Illuminate\Console\Command;

class BuildTheme extends Command
{
    // The name and signature of the console command
    protected $signature = 'coderstm:theme-build {name}';

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

        // Run the npm command to build the theme
        $npmBuildCommand = "npm run theme:build --name={$themeName}";

        $output = null;
        $resultCode = null;

        // Execute the npm build command
        exec($npmBuildCommand, $output, $resultCode);

        // Output npm response
        foreach ($output as $line) {
            $this->line($line);
        }

        // Check if the command was successful
        if ($resultCode === 0) {
            $this->info("Theme '{$themeName}' built successfully!");
        } else {
            $this->error("Failed to build the theme '{$themeName}'. Please check the npm output.");
        }

        return $resultCode;
    }
}
