<?php

namespace Coderstm\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class BuildTheme implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $themeName;
    protected $themePublic;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($themeName, $themePublic = false)
    {
        $this->themeName = $themeName;
        $this->themePublic = $themePublic;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Run the coderstm:theme-build command for the theme
        Artisan::call('coderstm:theme-build', [
            'name' => $this->themeName,
            '--theme-public' => $this->themePublic,
            '--path' => str_replace(base_path('/'), '', config('theme.base_path')),
        ]);
    }
}
