<?php

namespace Coderstm\Listeners;

use Illuminate\Support\Str;
use Coderstm\Models\AppSetting;
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
    }

    /**
     * Handle the event.
     *
     * @param  \RachidLaasri\LaravelInstaller\Events\EnvironmentSaved  $event
     * @return void
     */
    public function handle(EnvironmentSaved $event)
    {
        $request = $event->getRequest();
        if ($request->filled('license_key')) {
            AppSetting::updateValue('config', [
                'license_key' => $request->license_key
            ]);
        }
    }
}
