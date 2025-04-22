<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.16  |
    |              on 2025-04-22 01:53:54              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*

*/
 namespace Coderstm\Providers; use Coderstm\Models\Permission; use Illuminate\Support\Facades\Gate; use Illuminate\Support\Facades\Blade; use Illuminate\Support\Facades\Schema; use Illuminate\Support\ServiceProvider; class CoderstmPermissionsServiceProvider extends ServiceProvider { public function register() { } public function boot() { try { goto HSPYh; M9kCA: Blade::directive("\x67\162\157\165\160", function ($group, $guard = "\165\163\x65\162\163") { return "\x69\x66\x28\147\165\x61\162\x64\50\51\40\x3d\75\x20{$guard}\x20\46\46\40\x75\x73\145\x72\x28\x29\55\x3e\x68\x61\x73\107\162\x6f\x75\160\50{$group}\51\51\x20\72"; }); goto Izjmh; HSPYh: Permission::get()->map(function ($permission) { Gate::define($permission->scope, function ($user) use($permission) { return $user->hasPermission($permission->scope); }); }); goto M9kCA; Izjmh: Blade::directive("\x65\156\x64\147\162\x6f\165\160", function ($group) { return "\x65\x6e\x64\x69\146\x3b"; }); goto RZ1Yb; RZ1Yb: } catch (\Exception $e) { report($e); } } }