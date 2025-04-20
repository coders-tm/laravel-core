<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.16  |
    |              on 2025-04-20 17:43:38              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*

*/
 namespace Coderstm\Providers; use Coderstm\Services\Mix; use Coderstm\Services\Theme; use Illuminate\Support\Facades\File; use Illuminate\Support\Facades\Route; use Illuminate\Support\ServiceProvider; use Coderstm\Services\MaskSensitiveConfig; use Coderstm\Http\Middleware\RequestThemeMiddleware; class ThemeServiceProvider extends ServiceProvider { public function register() : void { $this->app->singleton(Mix::class); $this->app->singleton("\142\154\x61\x64\145\x2e\143\157\x6d\x70\151\154\145\162", function () { return new MaskSensitiveConfig($this->app["\x66\151\154\145\163"], $this->app["\x63\157\156\146\x69\147"]["\x76\151\x65\x77\x2e\x63\x6f\155\160\x69\x6c\x65\x64"]); }); } public function boot() : void { goto Em0HD; y5R_i: thG7u: goto I6mnz; IzWD7: Theme::set($theme); goto y5R_i; Em0HD: if (!($theme = settings("\164\x68\x65\x6d\145", "\x61\143\x74\x69\x76\145"))) { goto thG7u; } goto IzWD7; P9k3f: $kernel->pushMiddleware(RequestThemeMiddleware::class); goto sM7Za; I6mnz: $kernel = $this->app->make("\111\x6c\154\165\155\x69\x6e\x61\164\145\x5c\x43\157\156\x74\162\x61\x63\164\x73\134\110\x74\164\x70\134\113\145\162\x6e\145\x6c"); goto P9k3f; sM7Za: } }