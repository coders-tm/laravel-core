---
next: false
---

# Troubleshooting

[[toc]]

## No supported encrypter found. The cipher and / or key length are invalid.

    RuntimeException: No supported encrypter found. The cipher and / or key length are invalid.

This error would only occur if your test suite requires the encrypter. To solve this you can add a dummy `APP_KEY` or use a specific key to your application/package `phpunit.xml`.

```xml
<phpunit>
    // ...
    <php>
        <env name="APP_KEY" value="AckfSECXIvnK5r28GVIWUAxmbBSjTsmF"/> # [!code ++] # [!code focus]
    </php>
</phpunit>
```

## Why Testbench doesn't include any of the `App` classes.

The reason Testbench remove all the classes is to make sure that you would never depend on it when developing Laravel Packages. Classes such as `App\Http\Controllers\Controller` and `App\User` are simple to be added but the problem with these classes is that they can be either:

* Removed, moved to another location such as `App\Models\User`, or
* Renamed using `php artisan app:name Acme` which would rename `App\User` to `Acme\User`.

## Class `GuzzleHttp\Client` not found

If you plan to use the new **HTTP Client** in Laravel 7, you need to include `guzzlehttp/guzzle` to your package's `composer.json`:

```bash
composer require "guzzlehttp/guzzle"
```

::: warning 

We can't guarantee that any requirements in `laravel/laravel` will always be maintained as it is. A developer may remove any of the optional requirements such as `guzzlehttp/guzzle`, `fideloper/proxy`, `fruitcake/laravel-cors` or `laravel/tinker`.
:::

## Class `Illuminate\Database\Eloquent\Factory` not found

Starting from Laravel 8, `Illuminate\Database\Eloquent\Factory` has been pushed to `laravel/legacy-factories` package in favor of class based Factories.

As package developers, you have the option to either split the package version for Laravel 8 from the previous Laravel version and use the new class-based Factories or require `laravel/legacy-factories` to make a release while supporting lower versions as well. In both cases, the packages will need to require PHP 7.3 and above.

::: tip

The minimum PHP requirements are due to `laravel/legacy-factories` depending on `illuminate/macroable`.
:::

In order to use legacy factories on packages development supporting Laravel 8 and below without splitting the release you can opt to use the following:

```bash
composer require --dev "laravel/legacy-factories:^1.0.4"
```

Next, you need to ensure `orchestra/testbench` uses the minimum version supporting `laravel/legacy-factories` to avoid issues on the CI environment (if you're running tests on each version of Laravel or using `--prefer-lowest`).

| Laravel | Minimum Versions 
|:--------|:---------------
| 5.5     | `3.5.6`
| 5.6     | `3.6.7`
| 5.7     | `3.7.8`
| 5.8     | `3.8.6`
| 6.x     | `4.8.0`
| 7.x     | `5.2.0`

E.g: If you need to support minimum Laravel 5.6 here how the requirement should look like in `composer.json`:

```json
{
    "require-dev": {
        "orchestra/testbench": "~3.6.7 || ~3.7.8 || ~3.8.6 || ^4.8 || ^5.2 || ^6.0"
    }
}
```

### Converted to new class based factories but still facing this error

You need to check all your TestCase and ensure that there is no call to `$this->withFactories()`, autoloading class-based factories are handled by Composer, and `withFactories()` is only needed for legacy-based factories.

## Missing Browser Kit support after testing on Laravel 5.4

Replace `orchestra/testbench` with `orchestra/testbench-browser-kit` and follow [the installation guide](https://github.com/orchestral/testbench-browser-kit#installation).
