<?php

use Coderstm\Coderstm;
use Coderstm\Models\Tax;
use League\ISO3166\ISO3166;
use Illuminate\Support\Str;
use Coderstm\Models\AppSetting;
use Illuminate\Support\Optional;
use Symfony\Polyfill\Intl\Icu\Currencies;
use Illuminate\Support\Facades\Notification;
use Laravel\Cashier\Cashier;

if (!function_exists('guard')) {
    function guard()
    {
        if (request()->user()) {
            return request()->user()->guard;
        }
        return null;
    }
}

if (!function_exists('user')) {
    function user()
    {
        return request()->user();
    }
}

if (!function_exists('is_user')) {
    function is_user()
    {
        return guard() == 'users';
    }
}

if (!function_exists('is_admin')) {
    function is_admin()
    {
        return guard() == 'admins';
    }
}

if (!function_exists('base_url')) {
    function base_url($path = '')
    {
        if (!$path) return config('app.url');

        // Check if $path starts with a slash
        $separator = (substr($path, 0, 1) === '/') ? '' : '/';
        return config('app.url') . $separator . $path;
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '', $absolute = false)
    {
        // Check if $path starts with a slash
        $separator = (substr($path, 0, 1) === '/') ? '' : '/';

        // Get the base URL from config
        $baseUrl = config('coderstm.admin_url');

        // Check if absolute is true
        // Remove any extra path after the base URL
        if ($absolute) {
            $parts = parse_url($baseUrl);
            $baseUrl = $parts['scheme'] . '://' . $parts['host'];
            if (isset($parts['port'])) {
                $baseUrl .= ':' . $parts['port'];
            }
        }

        return $baseUrl . $separator . $path;
    }
}

if (!function_exists('app_url')) {
    function app_url($path = '')
    {
        // Check if $path starts with a slash
        $separator = (substr($path, 0, 1) === '/') ? '' : '/';
        return config('coderstm.app_url') . $separator . $path;
    }
}

if (!function_exists('is_active')) {
    function is_active(...$routes)
    {
        return request()->is($routes) ? 'active' : '';
    }
}

if (!function_exists('has_recaptcha')) {
    function has_recaptcha()
    {
        return !empty(config('recaptcha.site_key'));
    }
}

if (!function_exists('app_settings')) {
    function app_settings($key)
    {
        try {
            return AppSetting::findByKey($key);
        } catch (\Exception $e) {
            return collect();
        }
    }
}

if (!function_exists('opening_times')) {
    function opening_times()
    {
        return app_settings('opening-times')->map(function ($item, $key) {
            $item['is_today'] = now()->format('l') == $item['name'];
            return $item;
        });
    }
}

if (!function_exists('string_to_hex')) {
    function string_to_hex($name = 'Name')
    {
        $alphabet = range('A', 'Z');
        $numbers = collect(explode(' ', $name))->map(function ($item) use ($alphabet) {
            return array_search(mb_substr($item, 0, 1), $alphabet) + 1;
        })->sum();

        return sprintf("#%06x", $numbers * 3333);
    }
}

if (!function_exists('string_to_hsl')) {
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

if (!function_exists('model_log_name')) {
    function model_log_name($model)
    {
        if ($model->logName) {
            return $model->logName;
        }
        return Str::of(class_basename(get_class($model)))->snake()->replace('_', ' ')->title();
    }
}

if (!function_exists('format_amount')) {
    function format_amount($amount, $currency = null, $locale = null, array $options = [])
    {
        return Cashier::formatAmount($amount * 100, $currency, $locale, $options);
    }
}

if (!function_exists('currency_symbol')) {
    function currency_symbol($currency = null)
    {
        return Currencies::getSymbol($currency ?? config('cashier.currency'));
    }
}

if (!function_exists('admin_notify')) {
    function admin_notify($notification)
    {
        return Notification::route('mail', [
            config('coderstm.admin_email') => config('app.name')
        ])->notify($notification);
    }
}

if (!function_exists('get_lang_code')) {
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
if (!function_exists('app_lang')) {
    function app_lang()
    {
        try {
            $config = app_settings('config');
            $locale = $config['lang'] ?? 'en-US';

            return get_lang_code($locale);
        } catch (\Exception $e) {
            return 'en';
        }
    }
}

if (!function_exists('company_address')) {
    function company_address($html = false)
    {
        $address = optional((object) app_settings('address')->toArray());
        $keys = [
            'company' => $address->company,
            'line1' => $address->line1,
            'line2' => $address->line2,
            'city' => $address->city,
            'state' => $address->state ? "{$address->state}, {$address->postal_code}" : '',
            'country' => $address->country,
        ];
        return collect($keys)->filter()->implode($html ? '<br>' : ', ');
    }
}

if (!function_exists('replace_short_code')) {
    function replace_short_code($message = '', $replace = [])
    {
        $replace = array_merge([
            '{{APP_NAME}}' => config('app.name'),
            '{{SUPPORT_EMAIL}}' => config('coderstm.admin_email'),
            '{{BILLING_PAGE}}' => app_url('billing'),
            '{{MEMBER_PAGE}}' => config('coderstm.app_url'),
            '{{ADMIN_PAGE}}' => config('coderstm.admin_url'),
        ], $replace, Coderstm::$appShortCodes);

        foreach ($replace as $key => $value) {
            $message = str_replace($key, $value, $message);
        }

        return $message;
    }
}

if (!function_exists('has')) {
    /**
     * Provide access to optional objects.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     * @return mixed
     */
    function has($value = null, callable $callback = null)
    {
        $value = is_object($value) ? $value : (object) $value;

        if (is_null($callback)) {
            return new Optional($value);
        } elseif (!is_null($value)) {
            return $callback($value);
        }
    }
}

if (!function_exists('get_country_code')) {
    function get_country_code(string $country = '')
    {
        try {
            $country = (new ISO3166)->name($country);
            return $country['alpha2'];
        } catch (\Exception $e) {
            return '*';
        }
    }
}

if (!function_exists('country_taxes')) {
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
                    'type' => $item->compounded ? 'compounded' : 'normal'
                ]);
            })->toArray();
    }
}

if (!function_exists('default_tax')) {
    function default_tax()
    {
        $taxes = country_taxes(config('app.country'));
        if (count($taxes) > 0) {
            return $taxes;
        }
        return rest_of_world_tax();
    }
}

if (!function_exists('rest_of_world_tax')) {
    function rest_of_world_tax()
    {
        return country_taxes('*');
    }
}

if (!function_exists('billing_address_tax')) {
    function billing_address_tax(array $address = [])
    {
        if (isset($address['country']) && !empty($address['country'])) {
            $stateCode = isset($address['state_code']) ? $address['state_code'] : null;
            $taxes = country_taxes($address['country'], $stateCode);
            if (count($taxes) > 0) {
                return $taxes;
            }
        }

        return rest_of_world_tax();
    }
}

if (!function_exists('trans_status')) {
    function trans_status($action = null, $module = null, $attribute = null)
    {
        return trans('messages.module.' . $action, [
            'module' => trans_choice('modules.' . $module, 1),
            'type' => trans('messages.attributes.' . $attribute)
        ]);
    }
}

if (!function_exists('trans_module')) {
    function trans_module($action = null, $module = null, $count = 1)
    {
        return trans('messages.module.' . $action, ['module' => trans_choice('modules.' . $module, $count)]);
    }
}

if (!function_exists('trans_modules')) {
    function trans_modules($action = null, $module = null)
    {
        return trans_module($action, $module, 2);
    }
}

if (!function_exists('trans_attribute')) {
    function trans_attribute($key = null, $type = null)
    {
        return trans('messages.' . $key, ['type' => trans('messages.attributes.' . $type)]);
    }
}

if (!function_exists('html_text')) {
    function html_text($html = '')
    {
        $html = str_replace('<div><br>', "\n ", $html);
        $html = str_replace('<br>', "\n ", $html);

        return trim(preg_replace('/\s+/', ' ', strip_tags($html)));
    }
}
