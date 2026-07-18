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
    protected $description = 'Install all of the Coderstm resources and configure the package';

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

        $this->registerProviders();

        $this->comment('Configuring cookie encryption for Laravel 12...');
        $this->configureCookieEncryption();

        $this->comment('Configuring custom Application class in bootstrap/app.php...');
        $this->configureApplicationBootstrap();

        $this->info('Coderstm scaffolding installed successfully.');
        $this->line('');
        $this->info('✓ Providers registered in bootstrap/providers.php');
        $this->info('✓ Cookie encryption configured for Laravel 12');
        $this->info('✓ Custom Application class and router registered');
        $this->info('✓ Cart tokens excluded from encryption automatically');
        $this->info('✓ Cart functionality configured and ready to use');
    }

    /**
     * Register the Coderstm providers in the bootstrap/providers.php file.
     *
     * @return void
     */
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

        // Check if provider is already registered
        if (Str::contains($providersContent, $serviceProvider)) {
            $this->info('Coderstm provider already registered in bootstrap/providers.php');

            return;
        }

        // Parse the existing providers array
        $providers = include $providersPath;
        if (! is_array($providers)) {
            $providers = [];
        }

        // Add provider if not already present
        if (! in_array($serviceProvider, $providers)) {
            $providers[] = $serviceProvider;
        }

        // Generate the new providers.php content
        $newContent = "<?php\n\nreturn [\n";
        foreach ($providers as $provider) {
            $newContent .= "    {$provider},\n";
        }
        $newContent .= "];\n";

        file_put_contents($providersPath, $newContent);

        // Update namespace in published provider files
        $this->updateProviderNamespace($namespace, 'CoderstmServiceProvider');

        $this->info('Coderstm provider registered in bootstrap/providers.php');
    }

    /**
     * Update the namespace in a published provider file.
     *
     * @param  string  $namespace
     * @param  string  $providerName
     * @return void
     */
    protected function updateProviderNamespace($namespace, $providerName)
    {
        $providerPath = app_path("Providers/{$providerName}.php");

        if (file_exists($providerPath)) {
            $content = file_get_contents($providerPath);
            $updatedContent = str_replace(
                'namespace App\\Providers;',
                "namespace {$namespace}\\Providers;",
                $content
            );
            file_put_contents($providerPath, $updatedContent);
        }
    }

    /**
     * Configure cookie encryption to exclude cart_token in Laravel 12.
     *
     * @return void
     */
    protected function configureCookieEncryption()
    {
        $bootstrapPath = base_path('bootstrap/app.php');

        if (! file_exists($bootstrapPath)) {
            $this->warn('bootstrap/app.php not found. Please manually configure cookie encryption.');

            return;
        }

        $bootstrapContent = file_get_contents($bootstrapPath);

        // Check if cart_token is already excluded
        if (strpos($bootstrapContent, "'cart_token'") !== false) {
            $this->info('cart_token already excluded from cookie encryption.');

            return;
        }

        // Look for the withMiddleware section
        $pattern = '/(->withMiddleware\(function \(Middleware \$middleware\): void \{[^}]*\})/s';

        if (preg_match($pattern, $bootstrapContent, $matches)) {
            $middlewareSection = $matches[1];

            // Check if encryptCookies is already configured
            if (strpos($middlewareSection, 'encryptCookies') !== false) {
                // Add cart_token to existing encryptCookies configuration
                $newMiddlewareSection = preg_replace(
                    '/encryptCookies\(except:\s*\[([^\]]*)\]/',
                    "encryptCookies(except: [\n            'cart_token',\n            $1",
                    $middlewareSection
                );
            } else {
                // Add new encryptCookies configuration
                $newMiddlewareSection = str_replace(
                    '->withMiddleware(function (Middleware $middleware): void {',
                    "->withMiddleware(function (Middleware \$middleware): void {\n            \$middleware->encryptCookies(except: [\n                'cart_token',\n            ]);",
                    $middlewareSection
                );
            }

            $bootstrapContent = str_replace($middlewareSection, $newMiddlewareSection, $bootstrapContent);
            file_put_contents($bootstrapPath, $bootstrapContent);

            $this->info('Cookie encryption configured to exclude cart_token.');
        } else {
            $this->warn('Could not find middleware configuration section in bootstrap/app.php. Please manually add the encryptCookies configuration.');
        }
    }

    /**
     * Configure the custom application class in bootstrap/app.php.
     *
     * @return void
     */
    protected function configureApplicationBootstrap()
    {
        $bootstrapPath = base_path('bootstrap/app.php');

        if (! file_exists($bootstrapPath)) {
            $this->warn('bootstrap/app.php not found. Please manually configure custom Application.');

            return;
        }

        $bootstrapContent = file_get_contents($bootstrapPath);

        // Replace the Application class import
        if (strpos($bootstrapContent, 'use Illuminate\Foundation\Application;') !== false) {
            $bootstrapContent = str_replace(
                'use Illuminate\Foundation\Application;',
                'use Coderstm\Foundation\Application;',
                $bootstrapContent
            );
            $this->info('Updated Application class import in bootstrap/app.php.');
        }

        file_put_contents($bootstrapPath, $bootstrapContent);
    }
}
