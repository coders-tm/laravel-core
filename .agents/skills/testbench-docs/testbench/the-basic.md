# The Basic

[[toc]]

## Package Service Providers

To load your package service provider, override the `getPackageProviders`.

```php
class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app) # [!code ++:6] # [!code focus:6]
    {
        return [
            'Acme\AcmeServiceProvider',
        ];
    }
}
```

## Package Aliases

To load your package alias, override the `getPackageAliases`.

```php
class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Override application aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<string, class-string<\Illuminate\Support\Facades\Facade>>
     */
    protected function getPackageAliases($app) # [!code ++:6] # [!code focus:6]
    {
        return [
            'Acme' => 'Acme\Facade',
        ];
    }
}
```

## Package Auto-Discovery

By default Testbench doesn't enable any package discovery autoloading when running tests. However, you can change this to ignore specific packages using:

```php
class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Ignore package discovery from.
     *
     * @return array<int, string>
     */
    public function ignorePackageDiscoveriesFrom() # [!code ++:6] # [!code focus:6]
    {
        return [ 
            'laravel/passport',
        ];
    }
}
```

You may also enable auto-discovery for all vendor packages using the following:

```php
class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Ignore package discovery from.
     *
     * @return array<int, string>
     */
    public function ignorePackageDiscoveriesFrom() # [!code ++:4] # [!code focus:4]
    {
        return [];
    }
}
```

You can also enable auto-discovery globally by setting the following property to your TestCase class:

```php
class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Automatically enables package discoveries.
     *
     * @var bool
     */
    protected $enablesPackageDiscoveries = true; # [!code ++] # [!code focus]
}
```

::: warning Root Package Discovery

Be aware that the root package doesn't automate using package discovery on any version below `v7.11.0` and you need to define it using [Package Service Providers](#package-service-providers) or registering it to the application using `php vendor/bin/testbench package:discover` command.
:::

## Overriding Default Service Providers

You can also override the default application using the following commands:

```php
class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<class-string<\Illuminate\Support\ServiceProvider>, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function overrideApplicationBindings($app) # [!code ++:6] # [!code focus:6]
    {
        return [
            'Illuminate\View\ViewServiceProvider' => 'Acme\ViewServiceProvider',
        ];
    }
}
```


## Overriding `setUp()` method

Since `Orchestra\Testbench\TestCase` replace Laravel's `Illuminate\Foundation\Testing\TestCase`, if you need your own `setUp()` implementation, do not forget to call `parent::setUp()` and make sure proper declaration compatibility:

```php
class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void # [!code ++:14] # [!code focus:14]
    {
        // Code before application created.

        $this->afterApplicationCreated(function () {
            // Code after application created.
        });

        $this->beforeApplicationDestroyed(function () {
            // Code before application destroyed.
        });

        parent::setUp();
    }
}
```

## Overriding Console Kernel

You can easily swap Console Kernel for application bootstrap by overriding `resolveApplicationConsoleKernel()` method:

```php
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Resolve application Console Kernel implementation.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function resolveApplicationConsoleKernel($app) # [!code ++:6] # [!code focus:6]
    {
        $app->singleton(
            ConsoleKernel::class, 'Tests\Console\Kernel'
        );
    }
}
```

## Overriding HTTP Kernel

You can easily swap HTTP Kernel for application bootstrap by overriding `resolveApplicationHttpKernel()` method:

```php
use Illuminate\Contracts\Http\Kernel as HttpKernel;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Resolve application HTTP Kernel implementation.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function resolveApplicationHttpKernel($app) # [!code ++:6] # [!code focus:6]
    {
        $app->singleton(
            HttpKernel::class, 'Tests\Http\Kernel'
        );
    }
}
```


## Using Custom Laravel Skeleton

By default, Testbench provides a basic Laravel Skeleton. However, you can override the `applicationBasePath()` method from your TestCase class:

```php
use function Orchestra\Testbench\package_path;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Get Application base path.
     *
     * @return string
     */
    public static function applicationBasePath() # [!code ++:4] # [!code focus:4]
    {
        return package_path('skeleton');
    }
}
```

::: details FALLBACK METHOD

The method was only introduced in `v6.22.0`, if you need to override Laravel Skeleton on the project before the release then overrides `getBasePath` method instead:

```php
class TestCase extends \Orchestra\Testbench\TestCase 
{
    /**
     * Get base path.
     *
     * @return string
     */
    protected function getBasePath() 
    {
        return __DIR__.'/../skeleton';
    }
}
```
:::

## Testing Blade components

You can easily test blade components with Laravel's `Illuminate\Foundation\Testing\Concerns\InteractsWithViews` trait.

```php
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews; # [!code ++] # [!code focus]

class TestCase extends \Orchestra\Testbench\TestCase
{
    use InteractsWithViews; # [!code ++] # [!code focus]
}
```
