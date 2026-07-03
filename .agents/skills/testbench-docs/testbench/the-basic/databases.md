# Define Databases

It is recommended for a package to use `ServiceProvider::loadMigrationsFrom()` feature (it automatically handles migrations for packages when used in Laravel applications and during tests) or you can define migrations specifically for the tests environment using the recommended guide below.

## In-Memory SQLite Connection

To reduce setup configuration, you could use `testing` database connection (`:memory:` with `sqlite` driver) by defining it under PHPUnit Configuration File:

```xml
<phpunit>
    // ...
    <php>
        <env name="DB_CONNECTION" value="testing"/> # [!code ++] # [!code focus]
    </php>
</phpunit>
```

Otherwise, you can also use `Orchestra\Testbench\Attributes\WithEnv` attribute:

```php
use Orchestra\Testbench\Attributes\WithEnv; # [!code ++] # [!code focus]

#[WithEnv('DB_CONNECTION', 'testing')] # [!code ++] # [!code focus]
class TestCase extends \Orchestra\Testbench\TestCase
{
    //
}
```

Alternatively, you can also explicitly set it up under `defineEnvironment()`: 

```php
class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app) # [!code ++:4] # [!code focus:4]
    {
        $app['config']->set('database.default', 'testing');
    }
}
```

Lastly, you can also use `Orchestra\Testbench\Attributes\WithConfig` attribute:

```php{1,3}
use Orchestra\Testbench\Attributes\WithConfig; # [!code ++] # [!code focus]

#[WithConfig('database.default', 'testing')] # [!code ++] # [!code focus]
class TestCase extends \Orchestra\Testbench\TestCase 
{
    //
}
```

## Automatically execute migrations

By default, you can use the `RefreshDatabase` trait to *only* run package migrations defined through the package's service provider.

```php
use Illuminate\Foundation\Testing\RefreshDatabase; # [!code ++] # [!code focus]

class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase; # [!code ++] # [!code focus]
}
```

If you also need to run the default Laravel migrations such as the `users` table migration, see the [Run Laravel Migrations](#run-laravel-migrations) section.

## Manually execute migrations

Instead of just automatically migrating the database, you also manually configure migrations steps using the `defineDatabaseMigrations()` method:

```php
use function Orchestra\Testbench\artisan; # [!code ++]

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations() # [!code ++:8] # [!code focus:8]
    {
        artisan($this, 'migrate', ['--database' => 'testbench']);

        $this->beforeApplicationDestroyed(
            fn () => artisan($this, 'migrate:rollback', ['--database' => 'testbench'])
        );
    }
}
```

### Run Laravel Migrations

By default, Testbench doesn't execute the default Laravel migrations which include `users` and `password_resets` table. In order to run the migration just add the following command:

```php
use Orchestra\Testbench\Attributes\WithMigration; # [!code ++] # [!code focus]

#[WithMigration] # [!code ++] # [!code focus]
class TestCase extends \Orchestra\Testbench\TestCase
{
    //
}
```

You can also run migrations for `cache`, `queue`, `notifications` or `session` by providing additional paramters to `Orchestra\Testbench\Attributes\WithMigration` attribute:

```php
use Orchestra\Testbench\Attributes\WithMigration; 

#[WithMigration('laravel', 'cache', 'queue')] # [!code ++:2] # [!code focus:2]
#[WithMigration('session')]
class TestCase extends \Orchestra\Testbench\TestCase
{
    //
}
```

### Run Testing Migrations

To run migrations that are only used for testing purposes and not part of your package, add the following to your base test class:

```php
use function Orchestra\Testbench\workbench_path; # [!code hl]

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations() # [!code ++:6] # [!code focus:6]
    {
        $this->loadMigrationsFrom(
            workbench_path('database/migrations')
        );
    }
}
```

### Using Database Transactions

You may also utilize `DatabaseTransactions` for a migrated state using the following:

```php{1,5}
use Illuminate\Foundation\Testing\DatabaseTransactions; # [!code ++] # [!code focus]

class TestCase extends \Orchestra\Testbench\TestCase
{
    use DatabaseTransactions; # [!code ++] # [!code focus]
}
```

::: details NOTES & CONSIDERATIONS

* Your migration files have to suite Laravel's convention, e.g. `0000_00_00_000000_create_package_test_tables.php`.
* You may choose to put your migrations folder in `workbench/database/migrations`.
* You may choose to change your test-migrations class name to be different from the published class names, e.g. from `CreateUsersTable` to `CreateUsersTestTable` or otherwise you may encounter composer class loader collision.
:::

