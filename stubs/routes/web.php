<?php

use Illuminate\Support\Facades\Route;

/**
 * ------------------------------------------------------------
 * Default Web Routes
 * ------------------------------------------------------------
 * This file is used to register the default web routes. Keep this at the top of the file.
 * Your any custom routes should be placed here below this comment before the Coderstm Web Routes.
 * ------------------------------------------------------------
 */

// Your custom routes here

/**
 * ------------------------------------------------------------
 * Coderstm Web Routes
 * ------------------------------------------------------------
 * This file is used to register the Coderstm web routes.
 * It will check if the routes/coderstm/web.php file exists and
 * register the routes if it does. Keep this at the bottom of the file.
 * ------------------------------------------------------------
 */
if (file_exists(base_path('routes/coderstm/web.php'))) {
    Route::group([], base_path('routes/coderstm/web.php'));
}
