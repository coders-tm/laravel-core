<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.16  |
    |              on 2025-04-16 10:35:11              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*

*/
 namespace Coderstm\Providers; use Coderstm\Services\Mix; use Coderstm\Services\Theme; use Illuminate\Support\Facades\File; use Illuminate\Support\Facades\Route; use Illuminate\Support\ServiceProvider; use Coderstm\Services\MaskSensitiveConfig; use Coderstm\Http\Middleware\RequestThemeMiddleware; class ThemeServiceProvider extends ServiceProvider { public function register() : void { $this->app->singleton(Mix::class); $this->app->singleton("\x62\x6c\x61\x64\145\56\x63\x6f\155\160\x69\x6c\x65\162", function () { return new MaskSensitiveConfig($this->app["\x66\x69\154\145\163"], $this->app["\x63\x6f\x6e\146\x69\147"]["\x76\151\145\167\x2e\x63\x6f\x6d\160\x69\154\145\144"]); }); } public function boot() : void { goto s0bN5; FgOvj: $kernel->pushMiddleware(RequestThemeMiddleware::class); goto ewMFa; znVG6: $kernel = $this->app->make("\111\154\154\x75\155\x69\156\x61\164\x65\134\x43\157\156\164\162\141\x63\x74\163\x5c\110\164\164\160\x5c\113\145\x72\x6e\145\154"); goto FgOvj; H6AJc: y7UYF: goto znVG6; s0bN5: if (!($theme = settings("\164\x68\x65\x6d\145", "\141\x63\x74\x69\x76\x65"))) { goto y7UYF; } goto tBggu; tBggu: Theme::set($theme); goto H6AJc; ewMFa: } }