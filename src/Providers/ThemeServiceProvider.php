<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.16  |
    |              on 2025-04-22 01:53:54              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*

*/
 namespace Coderstm\Providers; use Coderstm\Services\Mix; use Coderstm\Services\Theme; use Illuminate\Support\Facades\File; use Illuminate\Support\Facades\Route; use Illuminate\Support\ServiceProvider; use Coderstm\Services\MaskSensitiveConfig; use Coderstm\Http\Middleware\RequestThemeMiddleware; class ThemeServiceProvider extends ServiceProvider { public function register() : void { $this->app->singleton(Mix::class); $this->app->singleton("\x62\154\141\x64\x65\x2e\x63\x6f\x6d\x70\x69\x6c\145\162", function () { return new MaskSensitiveConfig($this->app["\x66\x69\x6c\x65\x73"], $this->app["\143\x6f\x6e\146\151\x67"]["\x76\151\x65\x77\56\x63\157\155\x70\x69\154\145\x64"]); }); } public function boot() : void { goto HuBFg; liF72: dZWOZ: goto XP3uT; VAqWC: $kernel->pushMiddleware(RequestThemeMiddleware::class); goto rRb8Y; WRm3i: Theme::set($theme); goto liF72; XP3uT: $kernel = $this->app->make("\111\x6c\154\x75\x6d\151\156\x61\x74\145\134\x43\157\156\x74\x72\141\x63\x74\x73\134\x48\164\164\160\134\x4b\145\162\x6e\x65\154"); goto VAqWC; HuBFg: if (!($theme = settings("\x74\150\x65\155\x65", "\x61\x63\x74\x69\x76\x65"))) { goto dZWOZ; } goto WRm3i; rRb8Y: } }