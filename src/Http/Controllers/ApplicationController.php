<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Coderstm;
use Coderstm\Models\Task;
use Illuminate\Support\Str;
use Coderstm\Mail\TestEmail;
use Coderstm\Services\Theme;
use Illuminate\Http\Request;
use Coderstm\Models\AppSetting;
use Coderstm\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Coderstm\Http\Controllers\Controller;

class ApplicationController extends Controller
{
    public function stats(Request $request)
    {
        return response()->json([
            'total' => Coderstm::$subscriptionModel::query()->count(),
            'rolling' => Coderstm::$subscriptionModel::query()->active()->count(),
            'end_date' => Coderstm::$subscriptionModel::query()->ended()->count(),
            'free' => Coderstm::$subscriptionModel::query()->active()->free()->count(),
            'max_year' => Coderstm::$subscriptionModel::query()->max(DB::raw("DATE_FORMAT(subscriptions.created_at,'%Y')")),
            'min_year' => 2000,
            'unread_support' => Coderstm::$enquiryModel::onlyActive()->count(),
            'unread_tasks' => Task::onlyActive()->count(),
        ], 200);
    }

    public function getSettings($key)
    {
        return response()->json(settings($key), 200);
    }

    public function config(Request $request)
    {
        $response = [];

        $config = array_merge(settings('config'), [
            'domain' => config('coderstm.domain'),
            'app_url' => config('app.url'),
            'currency_symbol' => currency_symbol(),
        ]);

        if ($request->filled('includes')) {
            foreach ($request->includes ?? [] as $item) {
                if ($item === 'payment-methods') {
                    $response[$item] = PaymentMethod::toPublic();
                } else {
                    $response[$item] = settings($item);
                }
            }

            $response['config'] = $config;

            return response()->json($response, 200);
        }

        return response()->json($config, 200);
    }

    public function paymentMethods()
    {
        return response()->json(PaymentMethod::toPublic(), 200);
    }

    public function updateSettings(Request $request)
    {
        $rules = [
            'key' => 'required',
            'options' => 'array',
        ];

        $this->validate($request, $rules);

        $merge = in_array($request->key, ['config']);

        AppSetting::updateOptions($request->key, $request->options ?? [], $merge);

        // Clear the cache for the specific key
        $cacheKey = "app_config_{$request->key}";
        Cache::forget($cacheKey);

        return response()->json([
            'message' => trans('messages.settings_update')
        ], 200);
    }

    public function testMailConfig(Request $request)
    {
        $rules = [
            'to' => 'required|email',
        ];

        $this->validate($request, $rules);

        try {
            foreach ($request->input() as $key => $value) {
                Config::set("mail.$key", $value);
            }
            Mail::to($request->to)->send(new TestEmail());
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }

        return response()->json([
            'message' => 'Test email sent successfully!'
        ], 200);
    }

    public function shortCode(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        if (Str::startsWith($request->content, '[calendar')) {
            return $request->content;
        }

        return Blade::render($request->content);
    }

    public function theme()
    {
        $theme = false;
        $editor = [
            'styles' => [],
            'scripts' => ['~/js/app.js']
        ];

        // Check if the theme has an editor configuration
        $config = Theme::config();
        $theme = Theme::active();

        // Merge theme-specific editor assets with global ones
        if ($theme) {
            $editor = array_merge_recursive($editor, $config['editor']);
        }

        $editor['styles'][] = '~/css/app.css';

        // Map styles and scripts
        $editor['styles'] = $this->mapAssets($editor['styles'], $theme);
        $editor['scripts'] = $this->mapAssets($editor['scripts'], $theme);

        return response()->json($editor, 200);
    }

    private function mapAssets(array $assets, ?string $theme = null)
    {
        return collect($assets)->unique()->map(function ($asset) use ($theme) {
            // Return external URLs as is
            if (preg_match('/^(https?:\/\/|\/\/)/', $asset)) {
                return $asset;
            }

            // Handle assets prefixed with "~" (for mix or theme)
            if (strpos($asset, '~') === 0) {
                $asset = substr($asset, 1);
                $asset = ($theme) ? theme($asset, $theme) : mix($asset, 'statics');

                if (preg_match('/^(https?:\/\/|\/\/)/', $asset)) {
                    return (string) $asset;
                }

                return asset($asset);
            }

            // Fallback for normal asset paths
            return asset($asset);
        })->values();
    }
}
