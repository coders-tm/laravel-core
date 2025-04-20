<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.16  |
    |              on 2025-04-20 18:18:19              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*

*/
 namespace Coderstm\Providers; use Coderstm\Models\Permission; use Illuminate\Support\Facades\Gate; use Illuminate\Support\Facades\Blade; use Illuminate\Support\Facades\Schema; use Illuminate\Support\ServiceProvider; class CoderstmPermissionsServiceProvider extends ServiceProvider { public function register() { } public function boot() { try { goto c3qGC; c3qGC: Permission::get()->map(function ($permission) { Gate::define($permission->scope, function ($user) use($permission) { return $user->hasPermission($permission->scope); }); }); goto oXMqV; oXMqV: Blade::directive("\x67\x72\x6f\x75\160", function ($group, $guard = "\165\163\145\162\163") { return "\151\x66\x28\147\165\x61\x72\x64\x28\51\40\75\75\40{$guard}\40\x26\46\40\x75\x73\145\x72\50\x29\x2d\x3e\x68\141\163\107\x72\x6f\165\160\x28{$group}\x29\x29\40\72"; }); goto UfHh2; UfHh2: Blade::directive("\x65\x6e\144\x67\x72\x6f\165\160", function ($group) { return "\x65\156\144\x69\146\x3b"; }); goto mDLGz; mDLGz: } catch (\Exception $e) { report($e); } } }