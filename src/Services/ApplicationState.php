<?php

namespace Coderstm\Services;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;

class ApplicationState
{
    protected function isInitialized()
    {
        $flagFile = base_path('storage/.installed');

        return file_exists($flagFile);
    }

    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('testing') || app()->runningUnitTests()) {
            return $next($request);
        }
        if (config('coderstm.domain') == 'coderstm.com') {
            return $next($request);
        }
        if (! $this->isInitialized()) {
            return $next($request);
        }
        if ($request->is('*license/manage') || $request->is('*license/update') || $request->is('*install*')) {
            return $next($request);
        }
        $loader = app(ConfigLoader::class);
        if (! $loader->isValid()) {
            return $this->handleInvalidState();
        }
        $request->attributes->set('system.ready', true);

        return $next($request);
    }

    private function handleInvalidState()
    {
        if (request()->expectsJson()) {
            return response()->json(['error' => 'State Error', 'message' => 'Application state invalid'], 403);
        }
        try {
            return redirect()->route('license-manage');
        } catch (\Throwable $e) {
            return response()->view('coderstm::license-required', [], 403);
        }
    }

    public function manage()
    {
        return view('coderstm::license');
    }

    public function update()
    {
        $request = request();
        $request->validate(['email' => 'required|email|exists:admins,email', 'password' => 'required', 'license' => 'required'], ['email.required' => __('An email address is required.'), 'email.exists' => __('Your email address doens\'t exists.')]);
        if (\Illuminate\Support\Facades\Auth::guard('admins')->attempt($request->only(['email', 'password']))) {
            $user = $request->user('admins');
            \Illuminate\Support\Facades\Auth::guard('admins')->logout();
            if (! $user->is_active()) {
                throw ValidationException::withMessages(['email' => [__('Your account has been disabled.')]]);
            }
            Config::set('coderstm.license_key', $request->license);
            $envPath = base_path('.env');
            if (file_exists($envPath)) {
                $envContent = file_get_contents($envPath);
                $envContent = preg_replace('/^APP_LICENSE_KEY=.*/m', 'APP_LICENSE_KEY='.$request->license, $envContent);
                file_put_contents($envPath, $envContent);
            }
            $loader = app(ConfigLoader::class);
            $loader->reload();
            if ($loader->isValid()) {
                return redirect(admin_url('auth/login'));
            } else {
                throw ValidationException::withMessages(['license' => [__('The license key provided is not valid.')]]);
            }
        } else {
            throw ValidationException::withMessages(['password' => [__('Your password doesn\'t match with our records.')]]);
        }
    }
}
