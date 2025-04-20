<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.16  |
    |              on 2025-04-20 18:18:19              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*

*/
 namespace Coderstm\Providers; use Coderstm\Services\Mix; use Coderstm\Services\Theme; use Illuminate\Support\Facades\File; use Illuminate\Support\Facades\Route; use Illuminate\Support\ServiceProvider; use Coderstm\Services\MaskSensitiveConfig; use Coderstm\Http\Middleware\RequestThemeMiddleware; class ThemeServiceProvider extends ServiceProvider { public function register() : void { $this->app->singleton(Mix::class); $this->app->singleton("\x62\154\141\144\145\x2e\x63\x6f\155\x70\x69\x6c\x65\x72", function () { return new MaskSensitiveConfig($this->app["\x66\x69\x6c\145\x73"], $this->app["\x63\x6f\156\x66\151\147"]["\166\x69\145\x77\56\x63\x6f\x6d\x70\x69\154\145\x64"]); }); } public function boot() : void { goto zD5HW; jB7AJ: Theme::set($theme); goto mu102; zD5HW: if (!($theme = settings("\x74\150\x65\155\145", "\x61\x63\164\151\x76\145"))) { goto lkvm_; } goto jB7AJ; mu102: lkvm_: goto aEyra; aEyra: $kernel = $this->app->make("\111\x6c\154\x75\x6d\151\156\x61\x74\145\134\x43\157\156\164\162\x61\143\x74\163\134\x48\x74\x74\x70\134\x4b\x65\x72\156\145\x6c"); goto Q2Psb; Q2Psb: $kernel->pushMiddleware(RequestThemeMiddleware::class); goto fXkL7; fXkL7: } }