<?php

namespace Coderstm\Listeners;

use Illuminate\Support\Facades\Config;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Coderstm\LaravelInstaller\Events\EnvironmentSaved;

class UpdateLicenseKey
{

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \RachidLaasri\LaravelInstaller\Events\EnvironmentSaved  $event
     * @return void
     */
    public function handle(EnvironmentSaved $event)
    {
        $request = $event->request;
        if ($request->filled('license_key')) {
            Config::set('app.license_key', $request->license_key);
        }
    }
}
