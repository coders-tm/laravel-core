<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.16  |
    |              on 2025-04-16 10:35:11              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*

*/
 namespace Coderstm\Providers; use Coderstm\Models\Permission; use Illuminate\Support\Facades\Gate; use Illuminate\Support\Facades\Blade; use Illuminate\Support\Facades\Schema; use Illuminate\Support\ServiceProvider; class CoderstmPermissionsServiceProvider extends ServiceProvider { public function register() { } public function boot() { try { goto V8Kjt; V8Kjt: Permission::get()->map(function ($permission) { Gate::define($permission->scope, function ($user) use($permission) { return $user->hasPermission($permission->scope); }); }); goto gEwVd; dzcAW: Blade::directive("\145\x6e\x64\147\x72\x6f\165\x70", function ($group) { return "\145\156\x64\x69\146\73"; }); goto CPFJ5; gEwVd: Blade::directive("\x67\x72\157\165\160", function ($group, $guard = "\x75\163\145\x72\163") { return "\151\x66\50\x67\165\141\162\144\x28\51\40\75\x3d\40{$guard}\40\46\46\40\x75\163\145\x72\x28\51\55\x3e\150\x61\163\107\162\x6f\x75\160\50{$group}\51\51\x20\x3a"; }); goto dzcAW; CPFJ5: } catch (\Exception $e) { report($e); } } }