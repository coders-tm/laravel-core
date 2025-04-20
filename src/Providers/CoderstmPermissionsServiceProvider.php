<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.16  |
    |              on 2025-04-20 17:43:37              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*

*/
 namespace Coderstm\Providers; use Coderstm\Models\Permission; use Illuminate\Support\Facades\Gate; use Illuminate\Support\Facades\Blade; use Illuminate\Support\Facades\Schema; use Illuminate\Support\ServiceProvider; class CoderstmPermissionsServiceProvider extends ServiceProvider { public function register() { } public function boot() { try { goto sUg0T; sUg0T: Permission::get()->map(function ($permission) { Gate::define($permission->scope, function ($user) use($permission) { return $user->hasPermission($permission->scope); }); }); goto Qos4u; Qos4u: Blade::directive("\147\x72\x6f\x75\x70", function ($group, $guard = "\x75\163\x65\x72\x73") { return "\151\x66\x28\x67\x75\x61\162\144\50\x29\40\x3d\x3d\40{$guard}\40\x26\x26\40\165\163\x65\x72\x28\x29\x2d\x3e\x68\141\x73\x47\x72\x6f\165\x70\50{$group}\51\x29\x20\x3a"; }); goto f0wJQ; f0wJQ: Blade::directive("\145\156\144\x67\x72\157\165\x70", function ($group) { return "\x65\156\144\151\x66\x3b"; }); goto SOTNr; SOTNr: } catch (\Exception $e) { report($e); } } }