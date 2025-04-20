<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.16  |
    |              on 2025-04-20 18:09:16              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*

*/
 namespace Coderstm\Providers; use Coderstm\Models\Permission; use Illuminate\Support\Facades\Gate; use Illuminate\Support\Facades\Blade; use Illuminate\Support\Facades\Schema; use Illuminate\Support\ServiceProvider; class CoderstmPermissionsServiceProvider extends ServiceProvider { public function register() { } public function boot() { try { goto mlUXQ; QLqCR: Blade::directive("\147\162\157\165\x70", function ($group, $guard = "\165\x73\145\x72\163") { return "\151\x66\x28\x67\x75\x61\x72\144\50\51\x20\75\x3d\40{$guard}\x20\46\x26\x20\x75\163\145\x72\x28\51\55\x3e\150\x61\x73\x47\x72\157\165\x70\x28{$group}\x29\51\40\x3a"; }); goto qmTPL; qmTPL: Blade::directive("\145\x6e\x64\147\162\157\165\160", function ($group) { return "\x65\156\x64\x69\146\x3b"; }); goto nb1wd; mlUXQ: Permission::get()->map(function ($permission) { Gate::define($permission->scope, function ($user) use($permission) { return $user->hasPermission($permission->scope); }); }); goto QLqCR; nb1wd: } catch (\Exception $e) { report($e); } } }