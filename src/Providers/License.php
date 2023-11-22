<?php

namespace Coderstm\Providers;

use Closure;
use Carbon\Carbon;
use Coderstm\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class License
{
    private static $publicKey = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAq2jaX+9tYamXJgR4f+ZV
bcnoL9LJTv7fuj2w3zYEyswKgGm76CnwZtX998NmDiaQMKGDcMQn46iSHkpdtiFL
JK2uHwNxwPSeKHzpueLjoEmkQiZ/w2EhihrZs3oFTckxJ1mdcFfAOYRV7gGVcwZ9
3uvCGaNugCZOa6qZAvz7DDruBwXndkDWulDjetU/luT+ZDEy7nLIUrunrxkOhh//
2swkYwlkGe5sAhhhxzjaS8Z8iyMrpl19KiM0K3aHQQbQInJXQB5NDXB3DAP7Bnyn
d6b+81QqTuKsiyI3EBbxD1kwfz8ZjmIXItobdHg6DcfxojXUu53KL6UOgaYbVd9X
uUoUUQ+2xGVDkl9NFFT3/GxxZr181E159LD8WN+yxaNWtl2V+NJ6vAXVMfaAL6Gw
Uy0TMXpFRzNyv8loIJYVlgVus1cpYHprNn/BvSZ1t3TKdamKWcUbyur0ASU8WYNx
v1z9SUIF9Zy+1Z4bGDaX3fxDYTYfeRpVmupmKYMgl8l8WsA/2YEhFqlxG6GUW4Cw
C3hU6qOJZ+edJ734AJOJkjXHiD3CeFgOSQMhqMvHRUosdL807ZE7XO19v7YiPTG7
3jjwKsNaxi+CzucUNJY+IIqi3J8MdoinBi4VTzIzZpFihABED87hfZn4RQ/tr8vV
8RP0r6TWAkYaO/VOYUsRyQUCAwEAAQ==
-----END PUBLIC KEY-----';

    private static $privateKey = '-----BEGIN PRIVATE KEY-----
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
g82e6+PSlhdUyg4lNa1l/3vqdQji9Q==
-----END PRIVATE KEY-----';

    const LICENSE_NAME = 'LICENSE';

    private static function read()
    {
        $encryptedLicense = file_get_contents(storage_path(static::LICENSE_NAME));

        // Decrypt using the private key
        openssl_private_decrypt($encryptedLicense, $decryptedLicense, static::$privateKey);

        return json_decode($decryptedLicense, false);
    }

    public static function write(array $licenseContent)
    {
        // Encrypt using the public key
        openssl_public_encrypt(json_encode($licenseContent), $encryptedLicense, static::$publicKey);

        // Save the encrypted content to a file
        file_put_contents(storage_path(static::LICENSE_NAME), $encryptedLicense);

        return optional((object) $licenseContent);
    }

    public static function removeLicense(): void
    {
        if (static::hasLicense()) {
            unlink(storage_path(static::LICENSE_NAME));
        }
    }

    public static function hasLicense(): bool
    {
        return file_exists(storage_path(static::LICENSE_NAME));
    }

    private static function fetchLicense()
    {
        if (static::hasLicense()) {
            $license = static::read();
            if (Carbon::createFromTimestamp($license->expires_at)->gt(now())) {
                return $license;
            }
        }

        $response = Http::withToken(config('app.license_key'))
            ->post('https://api.coderstm.gomedia/licenses/check', [
                'domain' => config('coderstm.domain'),
                'options' => [
                    'root' => base_path()
                ]
            ]);

        if ($response->ok()) {
            $license = $response->json();
            return static::write(array_merge($license, [
                'expires_at' => now()->addMinutes(5)->timestamp,
            ]));
        }

        return optional((object) ['invalid' => true]);
    }

    public static function validate()
    {
        $license = static::fetchLicense();
        $active = $license->active && !$license->expired && !$license->invalid;
        $domainMatched = config('coderstm.domain') == $license->domain;
        $rootMatched = base_path() == $license->root;

        if ($active && $domainMatched && $rootMatched) {
            return true;
        }

        static::removeLicense();
        return false;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('*license/manage') || $request->is('*license/update')) {
            return $next($request);
        }

        $currentDomain = $request->getHost();
        $adminUrl = parse_url(config('coderstm.admin_url'));
        $adminDomain = $adminUrl['host'];

        // Check if the current domain matches the allowed domain
        if ($currentDomain == $adminDomain && static::validate() == false) {
            // Create a response instead of returning a view
            return redirect()->route('license-manage');
        }

        return $next($request);
    }

    public function manage(Request $request)
    {
        return view('coderstm::license');
    }

    public function update(Request $request)
    {
        $request->validate(
            [
                'email' => "required|email|exists:admins,email",
                'password' => 'required',
                'license' => 'required',
            ],
            [
                'email.required' => trans('coderstm::validation.email.required'),
                'email.exists' => trans('coderstm::validation.email.exists'),
            ]
        );

        if (Auth::guard('admins')->attempt($request->only(['email', 'password']))) {
            $user = $request->user('admins');
            Auth::guard('admins')->logout();

            // check user status
            if (!$user->is_active()) {
                throw ValidationException::withMessages([
                    'email' => ['Your account has been disabled.'],
                ]);
            }

            config('app.license_key', $request->license);

            AppSetting::updateValue('config', [
                'license_key' => $request->license
            ]);

            if (static::validate()) {
                return redirect(admin_url('auth/login'));
            } else {
                throw ValidationException::withMessages([
                    'license' => ['The license key provided is not valid.'],
                ]);
            }
        } else {
            throw ValidationException::withMessages([
                'password' => [trans('coderstm::validation.password.match')],
            ]);
        }
    }
}
