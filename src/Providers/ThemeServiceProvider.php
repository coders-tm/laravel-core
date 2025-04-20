<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.16  |
    |              on 2025-04-20 18:09:16              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*

*/
 namespace Coderstm\Providers; use Coderstm\Services\Mix; use Coderstm\Services\Theme; use Illuminate\Support\Facades\File; use Illuminate\Support\Facades\Route; use Illuminate\Support\ServiceProvider; use Coderstm\Services\MaskSensitiveConfig; use Coderstm\Http\Middleware\RequestThemeMiddleware; class ThemeServiceProvider extends ServiceProvider { public function register() : void { $this->app->singleton(Mix::class); $this->app->singleton("\x62\154\141\x64\145\x2e\x63\157\x6d\160\x69\154\145\x72", function () { return new MaskSensitiveConfig($this->app["\146\151\154\x65\x73"], $this->app["\143\x6f\x6e\x66\151\147"]["\166\x69\145\167\56\143\x6f\155\160\x69\x6c\x65\144"]); }); } public function boot() : void { goto LrEKU; LrEKU: if (!($theme = settings("\164\x68\x65\155\x65", "\141\x63\164\151\x76\x65"))) { goto m0vS5; } goto Sbxjs; TAUtG: $kernel = $this->app->make("\x49\154\154\x75\x6d\x69\156\x61\164\145\x5c\x43\x6f\x6e\x74\162\141\143\x74\x73\x5c\x48\164\164\160\134\113\145\x72\x6e\x65\154"); goto UOMkk; UOMkk: $kernel->pushMiddleware(RequestThemeMiddleware::class); goto Q9l1P; fVqYT: m0vS5: goto TAUtG; Sbxjs: Theme::set($theme); goto fVqYT; Q9l1P: } }