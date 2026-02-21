<?php

namespace Coderstm\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class BuildTheme implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $themeName;

    protected $themePublic;

    public function __construct($themeName, $themePublic = false)
    {
        $this->themeName = $themeName;
        $this->themePublic = $themePublic;
    }

    public function handle()
    {
        Artisan::call('coderstm:theme-build', ['name' => $this->themeName, '--theme-public' => $this->themePublic, '--path' => str_replace(base_path('/'), '', config('theme.base_path'))]);
    }
}
