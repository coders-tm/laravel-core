<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Coderstm;
use Coderstm\Models\Task;
use Coderstm\Mail\TestEmail;
use Illuminate\Http\Request;
use Coderstm\Models\AppSetting;
use Coderstm\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Coderstm\Http\Controllers\Controller;
use Coderstm\Services\SubscriptionReports;

class ApplicationController extends Controller
{
    public function stats(Request $request)
    {
        $reports = new SubscriptionReports($request);

        return response()->json([
            'total' => $reports->count(),
            'rolling' => $reports->onlyRolling()->count(),
            'end_date' => $reports->onlyEnds()->count(),
            'free' => $reports->onlyFree()->count(),
            'max_year' => Coderstm::$subscriptionModel::query()->max(DB::raw("DATE_FORMAT(subscriptions.created_at,'%Y')")),
            'min_year' => 2000,
            'unread_support' => Coderstm::$enquiryModel::onlyActive()->count(),
            'unread_tasks' => Task::onlyActive()->count(),
        ], 200);
    }

    public function getSettings($key)
    {
        return response()->json(AppSetting::findByKey($key), 200);
    }

    public function config(Request $request)
    {
        $response = [];
        $config = AppSetting::findByKey('config')->filter(function ($item, $key) {
            return !in_array($key, ['license_key']);
        });

        if ($request->filled('includes')) {
            foreach ($request->includes ?? [] as $item) {
                if ($item === 'payment-methods') {
                    $response[$item] = PaymentMethod::toPublic();
                } else {
                    $response[$item] = AppSetting::findByKey($item);
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

        return response()->json([
            'message' => trans('coderstm::messages.settings_update')
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
}
