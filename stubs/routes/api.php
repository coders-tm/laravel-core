<?php

use Illuminate\Support\Facades\Route;

if (file_exists(base_path('routes/coderstm/api.php'))) {
    if (config('coderstm.tunnel_domain')) {
        Route::domain(config('coderstm.tunnel_domain'))
            ->group(base_path('routes/coderstm/api.php'));
    }

    Route::group([], base_path('routes/coderstm/api.php'));
}
