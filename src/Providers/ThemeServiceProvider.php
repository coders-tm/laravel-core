<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.16  |
    |              on 2025-03-25 17:32:48              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*

*/
 namespace Coderstm\Providers; use Coderstm\Services\Mix; use Coderstm\Services\Theme; use Illuminate\Support\Facades\File; use Illuminate\Support\Facades\Route; use Illuminate\Support\ServiceProvider; use Coderstm\Services\MaskSensitiveConfig; use Coderstm\Http\Middleware\RequestThemeMiddleware; class ThemeServiceProvider extends ServiceProvider { public function register() : void { $this->app->singleton(Mix::class); $this->app->singleton("\x62\154\141\144\x65\x2e\x63\157\155\x70\151\154\145\162", function () { return new MaskSensitiveConfig($this->app["\146\151\x6c\145\x73"], $this->app["\143\x6f\x6e\146\151\147"]["\x76\151\145\x77\56\x63\157\155\x70\151\154\x65\144"]); }); } public function boot() : void { goto jqVK5; M9KRZ: fy8Q6: goto Fvd0r; HftJ7: $kernel->pushMiddleware(RequestThemeMiddleware::class); goto dIIu6; Fvd0r: $kernel = $this->app->make("\111\154\x6c\x75\x6d\x69\156\141\164\x65\134\x43\x6f\x6e\164\x72\141\143\x74\x73\x5c\110\164\x74\x70\134\x4b\145\162\156\x65\154"); goto HftJ7; jqVK5: if (!($theme = settings("\x74\x68\x65\155\145", "\141\143\164\151\x76\145"))) { goto fy8Q6; } goto ldF32; ldF32: Theme::set($theme); goto M9KRZ; dIIu6: } }