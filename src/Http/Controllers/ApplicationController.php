<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Coderstm;
use Coderstm\Models\Task;
use Illuminate\Http\Request;
use Coderstm\Models\AppSetting;
use Coderstm\Http\Controllers\Controller;

class ApplicationController extends Controller
{
    /**
     * Get stats.
     *
     * @return \Illuminate\Http\Response
     */
    public function stats(Request $request)
    {
        return response()->json([
            'unread_support' => Coderstm::$enquiryModel::onlyActive()->count(),
            'unread_tasks' => Task::onlyActive()->count(),
        ], 200);
    }

    /**
     * Get settings for a key.
     *
     * @return \Illuminate\Http\Response
     */
    public function getSettings($key)
    {
        return response()->json(AppSetting::findByKey($key), 200);
    }

    public function config()
    {
        return $this->getSettings('config');
    }

    /**
     * Update settings for a key.
     *
     * @return \Illuminate\Http\Response
     */
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
