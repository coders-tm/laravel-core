<?php

namespace Coderstm\Services;

use Coderstm\Models\Module;
use Coderstm\Models\PaymentMethod;
use Coderstm\Models\Permission;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class Helpers
{
    public static function location()
    {
        try {
            $ip = request()->ip();
            $location = request()->ipLocation();
            $agent = new Agent;
            $device = $agent->browser().' on '.$agent->platform();
            $time = now()->format('M d, Y \\a\\t g:i a \\U\\T\\C');

            return collect(['ip' => $ip, 'device' => $device, 'location' => $location ? "{$location->regionName}, {$location->countryCode}" : '', 'time' => $time]);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public static function loadConfigFromDatabase(): void
    {
        try {
            $overrideMap = config('coderstm.settings_override', ['config' => ['alias' => 'app', 'email' => ['coderstm.admin_email', 'mail.from.address'], 'name' => ['mail.from.name'], 'currency' => 'cashier.currency', 'timezone' => fn ($value) => date_default_timezone_set($value)]]);
            $allSettings = settings();
            foreach ($allSettings as $settingKey => $settingValues) {
                if (! is_iterable($settingValues)) {
                    continue;
                }
                $mappingRules = $overrideMap[$settingKey] ?? [];
                $configAlias = $mappingRules['alias'] ?? $settingKey;
                foreach ($settingValues as $property => $value) {
                    Config::set("{$configAlias}.{$property}", $value);
                    if (! isset($mappingRules[$property])) {
                        continue;
                    }
                    $mapping = $mappingRules[$property];
                    if (is_array($mapping)) {
                        foreach ($mapping as $configKey) {
                            Config::set($configKey, $value);
                        }
                    } elseif (is_callable($mapping)) {
                        $mapping($value);
                    } else {
                        Config::set($mapping, $value);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error("Failed to load config from database: {$e->getMessage()}");
        }
    }

    public static function loadPaymentMethodsConfig(): void
    {
        try {
            PaymentMethod::syncConfig();
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public static function isValidColor($color)
    {
        return preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $color);
    }

    public static function checkNpmInstallation(): void
    {
        $npmFile = base_path('storage/.npm');
        if (file_exists($npmFile)) {
            $npmBinPath = config('coderstm.npm_bin');
            $npmVersionCheck = shell_exec("{$npmBinPath}/npm -v 2>&1");
            if (! $npmVersionCheck) {
                throw new \Coderstm\Exceptions\NpmNotFoundException;
            }
            $npmTestCheck = shell_exec("{$npmBinPath}/npx mix --version 2>&1");
            if (strpos($npmTestCheck, 'ERR') !== false) {
                throw new \Coderstm\Exceptions\NpmNotInstalledException;
            }
            file_put_contents($npmFile, '');
        }
    }

    public static function singularizeDirectoryName(string $dirName): string
    {
        $excludedDirectories = ['js', 'css', 'sass', 'scss', 'img'];
        if (! in_array($dirName, $excludedDirectories)) {
            return Str::singular($dirName);
        }

        return $dirName;
    }

    public static function updateOrCreateModule($item, bool $remove = false): ?Module
    {
        if ($remove) {
            Module::where('name', $item['name'])->delete();

            return null;
        }
        $module = Module::updateOrCreate(['name' => $item['name']], ['icon' => $item['icon'], 'url' => $item['url'], 'show_menu' => isset($item['show_menu']) ? $item['show_menu'] : 1, 'sort_order' => $item['sort_order']]);
        $module->permissions()->whereNotIn('action', $item['sub_items'])->forceDelete();
        foreach ($item['sub_items'] as $item) {
            Permission::updateOrCreate(['scope' => Str::slug($module['name']).':'.Str::slug($item)], ['module_id' => $module['id'], 'action' => $item]);
        }

        return $module;
    }
}
