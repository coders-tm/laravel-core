<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Coderstm;
use Coderstm\Models\Task;
use Illuminate\Http\Request;
use Coderstm\Models\AppSetting;
use Coderstm\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class ApplicationController extends Controller
{
    public function stats(Request $request)
    {
        return response()->json([
            'unread_support' => Coderstm::$enquiryModel::onlyActive()->count(),
            'unread_tasks' => Task::onlyActive()->count(),
        ], 200);
    }

    public function getSettings($key)
    {
        return response()->json(AppSetting::findByKey($key), 200);
    }

    public function config()
    {
        return $this->getSettings('config');
    }

    public function location()
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->get('https://ipinfo.io');

        return $response->json();
    }

    public function updateSettings(Request $request)
    {
        $rules = [
            'key' => 'required',
            'options' => 'array',
        ];

        $this->validate($request, $rules);

        AppSetting::create($request->key, $request->options ?? []);

        return response()->json([
            'message' => trans('coderstm::messages.settings_update')
        ], 200);
    }
}
