---
prev: false
next: 
  text: 'The Basic'
  link: '/testbench/the-basic'
---

# Introduction

Testbench simplifies the process to create feature and integration tests for your Laravel packages without massive configuration and build steps. 

[[toc]]

## Installing

You can install **Testbench** using the following command:

```bash
composer require --dev "orchestra/testbench"
```

Next, you can run the following command to scaffold your package with the recommended setup:

```bash
vendor/bin/testbench workbench:install
```

## Version Compatibility

 Laravel  | Testbench
:---------|:----------
 5.x.x    | 3.x.x
 6.x      | 4.x
 7.x      | 5.x
 8.x      | 6.x
 9.x      | 7.x
 10.x     | 8.x
 11.x     | 9.x
 12.x     | 10.x

## Configuration

Please refer to [Configuration](/getting-started/configuration) documentation for further details.

## Getting Started

To use Testbench Component, all you need to do is extend `Orchestra\Testbench\TestCase` instead of `PHPUnit\Framework\TestCase`. The fixture `app` booted by `Orchestra\Testbench\TestCase` is predefined to follow the base application skeleton of Laravel.

```php
class TestCase extends \PHPUnit\Framework\TestCase # [!code --]
class TestCase extends \Orchestra\Testbench\TestCase # [!code ++] # [!code focus]
{
    //
}
```

### PHPUnit Configuration

You can separate your tests in your `phpunit.xml` file by providing different test suites, allowing you to run your Feature tests on demand.

For example:

```xml
<testsuites> # [!code focus]
    <testsuite name="Feature"> # [!code ++:3] # [!code focus:3]
        <directory suffix="Test.php">./tests/Feature</directory>
    </testsuite>
    <testsuite name="Unit">
        <directory suffix="Test.php">./tests/Unit</directory>
    </testsuite>
</testsuites> # [!code focus]
```

Run only your feature tests by running PHPUnit with the `--testsuite=Feature` option.

```bash
vendor/bin/phpunit --testsuite=Feature
```

### Autoloading using `testbench.yaml`

Testbench will use the configuration values defined in `testbench.yaml` and use its value when the `TestCase` class uses `Orchestra\Testbench\Concerns\WithWorkbench` trait:

```php
use Orchestra\Testbench\Concerns\WithWorkbench; # [!code ++] # [!code focus]

class TestCase extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench; # [!code ++] # [!code focus]
}
```

#### Package Service Providers

Using `WithWorkbench` will make `TestCase` uses `providers` configuration value from `testbench.yaml`:

```yaml
providers:
  - Laravel\Sanctum\SanctumServiceProvider
  - Workbench\App\Providers\WorkbenchServiceProvider
```

#### Using Custom Laravel Skeleton

Using `WithWorkbench` will make `TestCase` uses `laravel` configuration value from `testbench.yaml`:

```yaml
laravel: ./skeleton
```

