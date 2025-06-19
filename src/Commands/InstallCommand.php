<?php

namespace Coderstm\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coderstm:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the Coderstm resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Publishing Coderstm Service Provider...');
        $this->callSilent('vendor:publish', ['--tag' => 'coderstm-provider']);

        $this->comment('Publishing Coderstm Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'coderstm-config']);

        $this->comment('Publishing Coderstm Routes...');
        $this->callSilent('vendor:publish', ['--tag' => 'coderstm-routes']);

        $this->comment('Publishing Coderstm Views...');
        $this->callSilent('vendor:publish', ['--tag' => 'coderstm-views']);

        $this->comment('Publishing Coderstm Controllers...');
        $this->callSilent('vendor:publish', ['--tag' => 'coderstm-controllers']);

        $this->comment('Publishing Coderstm Models...');
        $this->callSilent('vendor:publish', ['--tag' => 'coderstm-models']);

        $this->comment('Publishing Coderstm Policies...');
        $this->callSilent('vendor:publish', ['--tag' => 'coderstm-policies']);

        $this->registerCoderstmRouteServiceProvider();
        $this->registerCoderstmServiceProvider();

        $this->info('Coderstm scaffolding installed successfully.');
    }

    /**
     * Register the Coderstm route service provider in the application configuration file.
     *
     * @return void
     */
    protected function registerCoderstmRouteServiceProvider()
    {
        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, $namespace . '\\Providers\\CoderstmRouteServiceProvider::class')) {
            return;
        }

        $lineEndingCount = [
            "\r\n" => substr_count($appConfig, "\r\n"),
            "\r" => substr_count($appConfig, "\r"),
            "\n" => substr_count($appConfig, "\n"),
        ];

        $eol = array_keys($lineEndingCount, max($lineEndingCount))[0];

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\RouteServiceProvider::class," . $eol,
            "// {$namespace}\\Providers\RouteServiceProvider::class," . $eol . "        {$namespace}\Providers\CoderstmRouteServiceProvider::class," . $eol,
            $appConfig
        ));

        file_put_contents(app_path('Providers/CoderstmRouteServiceProvider.php'), str_replace(
            "namespace App\Providers;",
            "namespace {$namespace}\Providers;",
            file_get_contents(app_path('Providers/CoderstmRouteServiceProvider.php'))
        ));
    }

    /**
     * Register the Coderstm service provider in the application configuration file.
     *
     * @return void
     */
    protected function registerCoderstmServiceProvider()
    {
        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, $namespace . '\\Providers\\CoderstmServiceProvider::class')) {
            return;
        }

        $lineEndingCount = [
            "\r\n" => substr_count($appConfig, "\r\n"),
            "\r" => substr_count($appConfig, "\r"),
            "\n" => substr_count($appConfig, "\n"),
        ];

        $eol = array_keys($lineEndingCount, max($lineEndingCount))[0];

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\CoderstmRouteServiceProvider::class," . $eol,
            "{$namespace}\\Providers\CoderstmRouteServiceProvider::class," . $eol . "        {$namespace}\Providers\CoderstmServiceProvider::class," . $eol,
            $appConfig
        ));

        file_put_contents(app_path('Providers/CoderstmServiceProvider.php'), str_replace(
            "namespace App\Providers;",
            "namespace {$namespace}\Providers;",
            file_get_contents(app_path('Providers/CoderstmServiceProvider.php'))
        ));
    }
}
