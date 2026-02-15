<?php

use Coderstm\Models\AppSetting;
use Coderstm\Models\Tax;
use Coderstm\Services\AdminNotification;
use Coderstm\Services\Mix;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Optional;
use Illuminate\Support\Str;
use Laravel\Cashier\Cashier;
use League\ISO3166\ISO3166;
use Symfony\Polyfill\Intl\Icu\Currencies;

if (! function_exists('guard')) {
    /**
     * Get the current user's guard or check if the current guard matches any of the provided guards.
     *
     * @param  string  ...$args  Guard names to check against (e.g., 'users', 'admins')
     * @return string|bool|null Returns the current guard name if no arguments provided,
     *                          returns true if current guard matches any provided guard,
     *                          returns false if no match, or null if no authenticated user
     */
    function guard(...$args)
    {
        $currentGuard = user('guard') ?? null;

        if (empty($args)) {
            return $currentGuard;
        }

        return in_array($currentGuard, $args);
    }
}

if (! function_exists('user')) {
    function user(?string $key = null, $default = null)
    {
        $user = auth('sanctum')->user() ?? Auth::user();

        if ($key && $user) {
            return data_get($user, $key, $default);
        }

        return $user;
    }
}

if (! function_exists('is_user')) {
    /**
     * Check if the current user is authenticated with the 'users' guard.
     *
     * @return bool
     */
    function is_user()
    {
        return guard('users');
    }
}

if (! function_exists('is_admin')) {
    /**
     * Check if the current user is authenticated with the 'admins' guard.
     *
     * @return bool
     */
    function is_admin()
    {
        return guard('admins');
    }
}

if (! function_exists('base_url')) {
    /**
     * Deprecated: use app_url().
     *
     * @deprecated 6.x Use app_url() instead. This helper will be removed in a future release.
     */
    function base_url($path = '', $query = [])
    {
        trigger_error('The base_url() helper is deprecated and will be removed in a future release. Use app_url() instead.', E_USER_DEPRECATED);

        return app_url($path, $query);
    }
}

if (! function_exists('app_url')) {
    function app_url($path = '', $query = [])
    {
        $baseUrl = rtrim(config('app.url'), '/');

        if (! $path && empty($query)) {
            return $baseUrl;
        }

        // Check if $path starts with a slash
        $separator = (substr($path, 0, 1) === '/') ? '' : '/';
        $url = $path ? $baseUrl.$separator.$path : $baseUrl;

        // Append query parameters if provided
        if (! empty($query)) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
    }
}

if (! function_exists('admin_url')) {
    function admin_url($path = '', $query = [])
    {
        // Check if $path starts with a slash
        $separator = (substr($path, 0, 1) === '/') ? '' : '/';

        // Get the base URL from config
        $baseUrl = rtrim(config('coderstm.admin_url'), '/');
        $url = $path ? $baseUrl.$separator.$path : $baseUrl;

        // Append query parameters if provided
        if (! empty($query)) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
    }
}

if (! function_exists('base_route')) {
    function base_route($path = '', $query = [])
    {
        if (empty($path)) {
            $url = '/';
        } else {
            // Check if $path starts with a slash
            $separator = (substr($path, 0, 1) === '/') ? '' : '/';
            $url = $separator.$path;
        }

        // Append query parameters if provided
        if (! empty($query)) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
    }
}

if (! function_exists('user_route')) {
    function user_route($path = '', $query = [])
    {
        return base_route($path, $query);
    }
}

if (! function_exists('admin_route')) {
    function admin_route($path = '', $query = [])
    {
        $prefix = config('coderstm.admin_prefix');
        $prefix = (substr($prefix, 0, 1) === '/') ? substr($prefix, 1) : $prefix;

        $fullPath = $prefix.($path ? '/'.ltrim($path, '/') : '');

        return base_route($fullPath, $query);
    }
}

if (! function_exists('is_active')) {
    function is_active(...$routes)
    {
        return request()->is($routes) ? 'active' : '';
    }
}

if (! function_exists('has_recaptcha')) {
    function has_recaptcha()
    {
        return ! empty(config('recaptcha.site_key'));
    }
}

if (! function_exists('app_settings')) {
    /**
     * Get the specified setting value.
     *
     * @param  string  $key
     * @return \Illuminate\Support\Collection
     *
     * @deprecated Use settings() helper function instead. This function will be removed in a future release.
     */
    function app_settings($key)
    {
        return AppSetting::findByKey($key);
    }
}

if (! function_exists('settings')) {
    /**
     * Get / set the specified setting value.
     *
     * If an array is passed as the key, we will assume you want to set multiple values.
     *
     * @param  string|array|null  $key
     * @param  mixed  $default
     * @return mixed|\Coderstm\Models\AppSetting
     */
    function settings($key = null, $default = null)
    {
        if (is_null($key)) {
            return \Coderstm\Models\AppSetting::getSettings();
        }

        if (is_array($key)) {
            foreach ($key as $k => $value) {
                $segments = explode('.', $k);
                $settingKey = array_shift($segments);

                if (empty($segments)) {
                    \Coderstm\Models\AppSetting::updateValue($settingKey, $value);
                } else {
                    $options = \Coderstm\Models\AppSetting::findByKey($settingKey); // Already returns array
                    array_set($options, implode('.', $segments), $value);
                    \Coderstm\Models\AppSetting::updateValue($settingKey, $options);
                }
            }

            return true;
        }

        return \Coderstm\Models\AppSetting::get($key, $default);
    }
}

if (! function_exists('array_set')) {
    /**
     * Set an array item to a given value using "dot" notation.
     *
     * @param  array  $array
     * @param  string  $key
     * @param  mixed  $value
     * @return array
     */
    function array_set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }
}

if (! function_exists('opening_times')) {
    function opening_times()
    {
        $times = settings('opening-times', []);

        return collect($times)->map(function ($item, $key) {
            $item['is_today'] = now()->format('l') == $item['name'];

            return $item;
        });
    }
}

if (! function_exists('string_to_hex')) {
    function string_to_hex($name = 'Name')
    {
        $alphabet = range('A', 'Z');
        $numbers = collect(explode(' ', $name))->map(function ($item) use ($alphabet) {
            return array_search(mb_substr($item, 0, 1), $alphabet) + 1;
        })->sum();

        return sprintf('#%06x', $numbers * 3333);
    }
}

if (! function_exists('string_to_hsl')) {
    function string_to_hsl($str, $saturation = 35, $lightness = 65)
    {
        $hash = 0;

        for ($i = 0; $i < strlen($str); $i++) {
            $hash = ord(mb_substr($str, $i, 1)) + (($hash << 5) - $hash);
        }

        $hue = $hash % 360;

        return "hsl($hue, $saturation%, $lightness%)";
    }
}

if (! function_exists('model_log_name')) {
    function model_log_name($model)
    {
        if ($model->logName) {
            return $model->logName;
        }

        return Str::of(class_basename(get_class($model)))->snake()->replace('_', ' ')->title();
    }
}

if (! function_exists('format_amount')) {
    function format_amount($amount, $currency = null, $locale = null, array $options = [])
    {
        $currency = strtoupper($currency ?? config('app.currency', 'USD'));
        $symbol = currency_symbol($currency);

        // If unsupported currency
        if (in_array($currency, ['RWF', 'JPY', 'KRW'])) {
            // If the currency symbol is more than one character
            if (mb_strlen($symbol) > 1) {
                return number_format($amount, 2).' '.$symbol;
            }

            return $symbol.number_format($amount, 2);
        }

        // If the currency symbol is more than one character
        if (mb_strlen($symbol) > 1) {
            return number_format($amount, 2).' '.$symbol;
        }

        return Cashier::formatAmount($amount * 100, $currency, $locale, $options);
    }
}

if (! function_exists('currency_symbol')) {
    function currency_symbol($currency = null)
    {
        return Currencies::getSymbol($currency ?? config('cashier.currency'));
    }
}

if (! function_exists('admin_notify')) {
    function admin_notify($notification)
    {
        return app(AdminNotification::class)(...func_get_args());
    }
}

if (! function_exists('get_lang_code')) {
    function get_lang_code($locale)
    {
        // Match the language code before '-' or '_'
        if (preg_match('/^([a-z]{2})[-_]/i', $locale, $matches)) {
            return strtolower($matches[1]);
        }

        // If no match found, return the given $locale
        return $locale;
    }
}
if (! function_exists('app_lang')) {
    function app_lang()
    {
        try {
            $locale = settings('config.lang', 'en-US');

            return get_lang_code($locale);
        } catch (\Throwable $e) {
            return 'en';
        }
    }
}

if (! function_exists('company_address')) {
    function company_address($html = false)
    {
        $address = settings('address', []);
        $addressObj = optional((object) $address);
        $keys = [
            'company' => $addressObj->company,
            'line1' => $addressObj->line1,
            'line2' => $addressObj->line2,
            'city' => $addressObj->city,
            'state' => $addressObj->state ? "{$addressObj->state}, {$addressObj->postal_code}" : '',
            'country' => $addressObj->country,
        ];

        return collect($keys)->filter()->implode($html ? '<br>' : ', ');
    }
}

if (! function_exists('replace_short_code')) {
    /**
     * Replace shortcodes in a message with data
     *
     * @param  string  $message  The message template with shortcodes
     * @param  array  $date  Can be structured data: ['user' => ['name' => 'John']]
     * @return string
     */
    function replace_short_code($message = '', $date = [])
    {
        $processor = app(\Coderstm\Services\ShortcodeProcessor::class);
        $replacements = $processor->process($date);

        foreach ($replacements as $key => $value) {
            if (is_scalar($value) || is_null($value)) {
                $message = str_replace($key, (string) $value, $message);
            }
        }

        return $message;
    }
}

if (! function_exists('has')) {
    /**
     * Provide access to optional objects.
     *
     * @param  mixed  $value
     * @return mixed
     */
    function has($value = null, ?callable $callback = null)
    {
        $value = is_object($value) ? $value : (object) $value;

        if (is_null($callback)) {
            return new Optional($value);
        } elseif (! is_null($value)) {
            return $callback($value);
        }
    }
}

if (! function_exists('get_country_code')) {
    function get_country_code($country)
    {
        if (empty($country)) {
            return '*';
        }

        try {
            $country = (new ISO3166)->name($country);

            return $country['alpha2'];
        } catch (\Throwable $e) {
            return '*';
        }
    }
}

if (! function_exists('country_taxes')) {
    function country_taxes($countryCode = null, $state = null)
    {
        $taxes = Tax::where('code', get_country_code($countryCode))
            ->orderBy('priority');

        if ($state) {
            $taxes->whereIn('state', ['*', $state]);
        } else {
            $taxes->whereIn('state', ['*']);
        }

        return $taxes->get()
            ->map(function ($item) {
                return array_merge($item->only(['label', 'rate']), [
                    'type' => $item->compounded ? 'compounded' : 'normal',
                ]);
            })->toArray();
    }
}

if (! function_exists('default_tax')) {
    function default_tax()
    {
        $taxes = country_taxes(config('app.country'));
        if (count($taxes) > 0) {
            return $taxes;
        }

        return rest_of_world_tax();
    }
}

if (! function_exists('rest_of_world_tax')) {
    function rest_of_world_tax()
    {
        return country_taxes('*');
    }
}

if (! function_exists('billing_address_tax')) {
    function billing_address_tax(array $address = [])
    {
        if (isset($address['country']) && ! empty($address['country'])) {
            $stateCode = isset($address['state_code']) ? $address['state_code'] : null;
            $taxes = country_taxes($address['country'], $stateCode);
            if (count($taxes) > 0) {
                return $taxes;
            }
        }

        return rest_of_world_tax();
    }
}

if (! function_exists('trans_status')) {
    function trans_status($action = null, $module = null, $attribute = null)
    {
        // Map action to the appropriate message key
        $messageKey = match ($action) {
            'store' => ':module has been created successfully!',
            'update' => ':module has been updated successfully!',
            'destroy' => ':module has been deleted successfully!',
            'force_destroy' => ':module has been permanently deleted successfully!',
            'restore' => ':module has been restored successfully!',
            default => ':module has been created successfully!',
        };

        return __($messageKey, [
            'module' => trans_choice($module, 1),
            'type' => __($attribute),
        ]);
    }
}

if (! function_exists('trans_module')) {
    function trans_module($action = null, $module = null, $count = 1)
    {
        // Map action to the appropriate message key
        $messageKey = match ($action) {
            'store' => ':module has been created successfully!',
            'update' => ':module has been updated successfully!',
            'destroy' => ':module has been deleted successfully!',
            'force_destroy' => ':module has been permanently deleted successfully!',
            'restore' => ':module has been restored successfully!',
            default => ':module has been created successfully!',
        };

        return __($messageKey, ['module' => trans_choice($module, $count)]);
    }
}

if (! function_exists('trans_modules')) {
    function trans_modules($action = null, $module = null)
    {
        return trans_module($action, $module, 2);
    }
}

if (! function_exists('trans_attribute')) {
    function trans_attribute($key = null, $type = null)
    {
        // Map common attribute types
        $typeTranslation = match ($type) {
            'active' => __('active'),
            'deactive' => __('deactive'),
            'archived' => __('archived'),
            'unarchive' => __('unarchive'),
            'marked' => __('marked'),
            'unmarked' => __('unmarked'),
            default => __($type),
        };

        return __($key, ['type' => $typeTranslation]);
    }
}

if (! function_exists('html_text')) {
    function html_text($html = '')
    {
        $html = str_replace('<div><br>', "\n ", $html);
        $html = str_replace('<br>', "\n ", $html);

        return trim(preg_replace('/\s+/', ' ', strip_tags($html)));
    }
}

if (! function_exists('theme')) {
    /**
     * Get the path to a versioned theme's Mix file.
     *
     * @param  string  $path
     * @param  string  $themeName
     * @return \Illuminate\Support\HtmlString|string
     *
     * @throws \Exception
     */
    function theme($path, $themeName = null)
    {
        return app(Mix::class)(...func_get_args());
    }
}

if (! function_exists('blog')) {
    /**
     * Get the current blog from the request.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed|\Coderstm\Models\Blog
     */
    function blog($key = null, $default = null)
    {
        if ($key) {
            return request()->input('blog') ? optional(request()->input('blog'))->$key ?? $default : $default;
        }

        return request()->input('blog');
    }
}

if (! function_exists('fluent_data')) {
    /**
     * Recursively any data to FluentData object
     */
    function fluent_data(mixed $data): \Coderstm\Support\FluentData
    {
        return new \Coderstm\Support\FluentData($data);
    }
}
