<?php

namespace Coderstm\Services;

use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Coderstm\Models\PaymentMethod;
use Illuminate\Support\Facades\Config;
use Stevebauman\Location\Facades\Location;

class Helpers
{
    public static function location()
    {
        try {
            $ip = request()->ip();
            $location = Location::get($ip);
            $agent = new Agent();
            $device = $agent->browser() . ' on ' . $agent->platform();
            $time = now()->format('M d, Y \a\t g:i a \U\T\C');
            return collect([
                'ip' =>  $ip,
                'device' => $device,
                'location' => $location ? "{$location->regionName}, {$location->countryCode}" : '',
                'time' => $time,
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function loadConfigFromDatabase(...$keys): void
    {
        try {
            $options = [
                'config' => [
                    'alias' => 'app',
                    'email' => [
                        'coderstm.admin_email',
                        'mail.from.address',
                    ],
                    'name' => ['mail.from.name'],
                    'currency' => 'cashier.currency',
                    'timezone' => fn($value) => date_default_timezone_set($value),
                ]
            ];

            foreach ($keys as $key) {
                // Determine the alias to use, defaulting to the key if not specified
                $option = $options[$key] ?? [];
                $alias = $option['alias'] ?? $key;

                // Fetch settings from the database
                foreach (app_settings($key) as $attr => $value) {
                    // Set the configuration value in the application's config
                    Config::set("$alias.$attr", $value);

                    // Apply any specific logic defined in the $config array
                    if (isset($option[$attr])) {
                        $attribute = $option[$attr];

                        if (is_array($attribute)) {
                            // If it's an array, set multiple config values
                            foreach ($attribute as $item) {
                                Config::set($item, $value);
                            }
                        } elseif (is_callable($attribute)) {
                            // If it's a callable (e.g., a function), execute it
                            $attribute($value);
                        } else {
                            // Otherwise, set the value directly
                            Config::set($attribute, $value);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function loadPaymentMethodsConfig(): void
    {
        try {
            // Load cashier config from app payment methods table
            if ($stripe = PaymentMethod::stripe()) {
                config([
                    'cashier.key' => $stripe->configs['API_KEY'],
                    'cashier.secret' => $stripe->configs['API_SECRET'],
                    'cashier.webhook.secret' => $stripe->configs['WEBHOOK_SECRET'],
                ]);
            }

            // Load paypal config from app payment methods table
            if ($paypal = PaymentMethod::paypal()) {
                $mode = $paypal->test_mode ? 'sandbox' : 'live';
                config([
                    'paypal.mode' => $mode,
                    "paypal.{$mode}.client_id" => $paypal->configs['CLIENT_ID'],
                    "paypal.{$mode}.client_secret" => $paypal->configs['CLIENT_SECRET'],
                    'paypal.notify_url' => $paypal->webhook,
                ]);
            }

            // Load razorpay config from app payment methods table
            if ($razorpay = PaymentMethod::razorpay()) {
                config([
                    "razorpay.key_id" => $razorpay->configs['API_KEY'],
                    "razorpay.key_secret" => $razorpay->configs['API_SECRET'],
                ]);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Check if the provided string is a valid CSS color.
     *
     * @param  string  $color
     * @return bool
     */
    public static function isValidColor($color)
    {
        // Regex to match valid hex color codes (3 or 6 characters) or named colors
        return preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $color);
    }

    /**
     * Check if npm is installed and the test command can be executed.
     *
     * This method will create a `.npm` file if npm is successfully installed
     * and the test command executes without errors.
     *
     * @throws \Coderstm\Exceptions\NpmNotFoundException If npm is not installed on the server.
     * @throws \Coderstm\Exceptions\NpmNotInstalledException If the npm test command fails to execute successfully.
     */
    public static function checkNpmInstallation(): void
    {
        // Define the path for the .npm file
        $npmFile = base_path('storage/.npm');

        // Check if the .npm file exists
        if (file_exists($npmFile)) {
            // Retrieve the npm binary path from the configuration
            $npmBinPath = config('coderstm.npm_bin');

            // Check if npm is installed by fetching the version
            $npmVersionCheck = shell_exec("{$npmBinPath}/npm -v 2>&1");

            if (!$npmVersionCheck) {
                throw new \Coderstm\Exceptions\NpmNotFoundException;
            }

            // Check if npm test command works correctly
            $npmTestCheck = shell_exec("{$npmBinPath}/npx mix --version 2>&1");

            if (strpos($npmTestCheck, 'ERR') !== false) {
                throw new \Coderstm\Exceptions\NpmNotInstalledException;
            }

            // Create the .npm file to indicate that npm is installed
            file_put_contents($npmFile, '');
        }
    }

    /**
     * Convert a directory name to its singular form if not in the excluded list.
     *
     * @param string $dirName The directory name to convert.
     * @return string The singular form of the directory name or the original name if excluded.
     */
    public static function singularizeDirectoryName(string $dirName): string
    {
        // Define the array of names that should not be made singular
        $excludedDirectories = ['js', 'css', 'sass', 'scss', 'img'];

        // Check if the directory name is not in the excluded list
        if (!in_array($dirName, $excludedDirectories)) {
            return Str::singular($dirName); // Return the singular form
        }

        return $dirName; // Return the original name
    }
}
