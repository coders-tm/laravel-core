<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Coderstm;
use Coderstm\Models\Task;
use Coderstm\Mail\TestEmail;
use Illuminate\Http\Request;
use Coderstm\Models\AppSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Coderstm\Http\Controllers\Controller;
use Stevebauman\Location\Facades\Location;

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
        return response()->json(Location::get(request()->ip()), 200);
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
