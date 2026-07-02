<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Models\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function show(Log $log)
    {
        return response()->json($log->load(['user', 'media']), 200);
    }

    public function update(Request $request, Log $log)
    {
        $rules = ['message' => 'required'];
        $this->validate($request, $rules);
        $log->update($request->input());
        if ($request->has('media')) {
            $log->setMedia($request->input('media'));
        }

        return response()->json(['data' => $log, 'message' => __('Log has been updated successfully!')], 200);
    }

    public function destroy(Log $log)
    {
        $log->delete();

        return response()->json(['message' => __('Log has been deleted successfully!')], 200);
    }

    public function reply(Request $request, Log $log)
    {
        $rules = ['message' => 'required'];
        $this->validate($request, $rules);
        $log = $log->reply()->create($request->input());
        if ($request->has('media')) {
            $log->setMedia($request->input('media'));
        }

        return response()->json(['data' => $log->load('admin'), 'message' => __('Log reply has been created successfully!')], 200);
    }
}
