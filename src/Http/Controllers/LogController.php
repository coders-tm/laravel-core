<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Http\Controllers\Controller;
use Coderstm\Models\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{

    /**
     * Display the specified resource.
     *
     * @param  \Coderstm\Models\Log $log
     * @return \Illuminate\Http\Response
     */
    public function show(Log $log)
    {
        return response()->json($log->load([
            'user',
            'media',
        ]), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Coderstm\Models\Log $log
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Log $log)
    {
        // Set rules
        $rules = [
            'message' => 'required',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        $log->update($request->input());

        // Update media
        if ($request->has('media')) {
            $log->setMedia($request->input('media'));
        }

        return response()->json([
            'data' => $log->fresh()->load([
                'admin',
                'media',
            ]),
            'message' => trans('messages.logs.updated'),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Coderstm\Models\Log $log
     * @return \Illuminate\Http\Response
     */
    public function destroy(Log $log)
    {
        $log->delete();
        return response()->json([
            'message' => trans('messages.logs.destroy'),
        ], 200);
    }

    /**
     * Store a reply to specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Coderstm\Models\Log $log
     * @return \Illuminate\Http\Response
     */
    public function reply(Request $request, Log $log)
    {
        $rules = [
            'message' => 'required',
        ];

        $this->validate($request, $rules);

        $log = $log->reply()->create($request->input());

        // Update media
        if ($request->has('media')) {
            $log->setMedia($request->input('media'));
        }

        return response()->json([
            'data' => $log->load([
                'admin',
                'media',
            ]),
            'message' => trans('messages.logs.reply'),
        ], 200);
    }
}
