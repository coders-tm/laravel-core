<?php

namespace Coderstm\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    protected $signature = 'coderstm:install';

    protected $description = 'Install all of the Coderstm resources and configure the package';

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
        $this->registerProviders();
        $this->comment('Configuring cookie encryption for Laravel 12...');
        $this->configureCookieEncryption();
        $this->info('Coderstm scaffolding installed successfully.');
        $this->line('');
        $this->info('✓ Providers registered in bootstrap/providers.php');
        $this->info('✓ Cookie encryption configured for Laravel 12');
        $this->info('✓ Cart tokens excluded from encryption automatically');
        $this->info('✓ Cart functionality configured and ready to use');
    }

    protected function registerProviders()
    {
        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());
        $providersPath = base_path('bootstrap/providers.php');
        if (! file_exists($providersPath)) {
            $this->warn('bootstrap/providers.php not found. Please manually register the providers.');

            return;
        }
        $providersContent = file_get_contents($providersPath);
        $serviceProvider = "{$namespace}\\Providers\\CoderstmServiceProvider::class";
        if (Str::contains($providersContent, $serviceProvider)) {
            $this->info('Coderstm provider already registered in bootstrap/providers.php');

            return;
        }
        $providers = (include $providersPath);
        if (! is_array($providers)) {
            $providers = [];
        }
        if (! in_array($serviceProvider, $providers)) {
            $providers[] = $serviceProvider;
        }
        $newContent = "<?php\n\nreturn [\n";
        foreach ($providers as $provider) {
            $newContent .= "    {$provider},\n";
        }
        $newContent .= "];\n";
        file_put_contents($providersPath, $newContent);
        $this->updateProviderNamespace($namespace, 'CoderstmServiceProvider');
        $this->info('Coderstm provider registered in bootstrap/providers.php');
    }

    protected function updateProviderNamespace($namespace, $providerName)
    {
        $providerPath = app_path("Providers/{$providerName}.php");
        if (file_exists($providerPath)) {
            $content = file_get_contents($providerPath);
            $updatedContent = str_replace('namespace App\\Providers;', "namespace {$namespace}\\Providers;", $content);
            file_put_contents($providerPath, $updatedContent);
        }
    }

    protected function configureCookieEncryption()
    {
        $bootstrapPath = base_path('bootstrap/app.php');
        if (! file_exists($bootstrapPath)) {
            $this->warn('bootstrap/app.php not found. Please manually configure cookie encryption.');

            return;
        }
        $bootstrapContent = file_get_contents($bootstrapPath);
        if (strpos($bootstrapContent, "'cart_token'") !== false) {
            $this->info('cart_token already excluded from cookie encryption.');

            return;
        }
        $pattern = '/(->withMiddleware\\(function \\(Middleware \\$middleware\\): void \\{[^}]*\\})/s';
        if (preg_match($pattern, $bootstrapContent, $matches)) {
            $middlewareSection = $matches[1];
            if (strpos($middlewareSection, 'encryptCookies') !== false) {
                $newMiddlewareSection = preg_replace('/encryptCookies\\(except:\\s*\\[([^\\]]*)\\]/', "encryptCookies(except: [\n            'cart_token',\n            \$1", $middlewareSection);
            } else {
                $newMiddlewareSection = str_replace('->withMiddleware(function (Middleware $middleware): void {', "->withMiddleware(function (Middleware \$middleware): void {\n            \$middleware->encryptCookies(except: [\n                'cart_token',\n            ]);", $middlewareSection);
            }
            $bootstrapContent = str_replace($middlewareSection, $newMiddlewareSection, $bootstrapContent);
            file_put_contents($bootstrapPath, $bootstrapContent);
            $this->info('Cookie encryption configured to exclude cart_token.');
        } else {
            $this->warn('Could not find middleware configuration section in bootstrap/app.php. Please manually add the encryptCookies configuration.');
        }
    }
}
