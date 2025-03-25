<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.16  |
    |              on 2025-03-25 17:32:47              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*

*/
 namespace Coderstm\Providers; use Coderstm\Models\Permission; use Illuminate\Support\Facades\Gate; use Illuminate\Support\Facades\Blade; use Illuminate\Support\Facades\Schema; use Illuminate\Support\ServiceProvider; class CoderstmPermissionsServiceProvider extends ServiceProvider { public function register() { } public function boot() { try { goto d6Tp7; d6Tp7: Permission::get()->map(function ($permission) { Gate::define($permission->scope, function ($user) use($permission) { return $user->hasPermission($permission->scope); }); }); goto ChJX1; ChJX1: Blade::directive("\147\162\x6f\165\160", function ($group, $guard = "\165\163\145\x72\163") { return "\x69\146\50\x67\165\x61\162\x64\x28\51\40\75\x3d\x20{$guard}\40\46\x26\40\x75\163\145\162\50\51\55\x3e\x68\x61\163\x47\162\157\x75\160\50{$group}\51\51\x20\72"; }); goto Av3SN; Av3SN: Blade::directive("\x65\x6e\144\x67\x72\x6f\x75\x70", function ($group) { return "\145\x6e\144\x69\x66\x3b"; }); goto PCNzu; PCNzu: } catch (\Exception $e) { report($e); } } }