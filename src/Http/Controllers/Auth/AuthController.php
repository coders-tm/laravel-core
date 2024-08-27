<?php

namespace Coderstm\Http\Controllers\Auth;

use Coderstm\Coderstm;
use Coderstm\Models\Log;
use Coderstm\Enum\AppStatus;
use Illuminate\Http\Request;
use Coderstm\Services\Helpers;
use Coderstm\Events\UserSubscribed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Coderstm\Notifications\UserLogin;
use Coderstm\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request, $guard = 'users')
    {
        $request->validate(
            [
                'email' => "required|email|exists:{$guard},email",
                'password' => 'required',
            ],
            [
                'email.required' => trans('coderstm::validation.email.required'),
                'email.exists' => trans('coderstm::validation.email.exists'),
            ]
        );

        if (Auth::guard($guard)->attempt($request->only(['email', 'password']))) {
            $user = $request->user($guard);
            Auth::guard($guard)->logout();

            // check user status
            if (!$user->is_active()) {
                abort(403, trans('coderstm::messages.account_disabled'));
            }

            try {
                // create log
                $loginLog = $user->logs()->create([
                    'type' => 'login',
                    'options' => Helpers::location()
                ]);

                // send login alert to user if smtp configured
                $user->notify(new UserLogin($loginLog));
            } catch (\Exception $e) {
                $user->logs()->create([
                    'type' => 'login-alert',
                    'status' => Log::STATUS_ERROR,
                    'message' => $e->getMessage(),
                ]);
            }

            // delete old token with requested device
            $user->tokens()->where('name', $request->device_id)->delete();

            // create and return user with token
            $token = $user->createToken($request->device_id, [$guard]);

            return response()->json([
                'user' => $user->toLoginResponse(),
                'token' => $token->plainTextToken,
            ], 200);
        } else {
            throw ValidationException::withMessages([
                'password' => [trans('coderstm::validation.password.match')],
            ]);
        }
    }

    public function signup(Request $request, $guard = 'users')
    {
        $rules = [
            'email' => 'required|email|unique:users',
            'first_name' => 'required',
            'last_name' => 'required',
            'phone_number' => 'required',
            'line1' => 'required',
            'city' => 'required',
            'postal_code' => 'required',
            'country' => 'required',
            'password' => 'required|min:6|confirmed',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        $request->merge([
            'password' => Hash::make($request->password),
            'status' => AppStatus::PENDING->value
        ]);

        // create the user
        $user = Coderstm::$userModel::create($request->only([
            'title',
            'email',
            'first_name',
            'last_name',
            'company_name',
            'email',
            'phone_number',
            'password',
            'status',
        ]));

        // add address to the user
        $user->updateOrCreateAddress($request->input());

        event(new UserSubscribed($user));

        // create and return user with token
        $token = $user->createToken($request->device_id, [$guard]);

        $user->logs()->create([
            'type' => 'login'
        ]);

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
        ], 200);
    }

    public function logout(Request $request, $guard = 'users')
    {
        try {
            $request->user()->currentAccessToken()->delete();
        } catch (\Throwable $th) {
            report($th);
        }

        return response()->json([
            'message' => trans('coderstm::messages.logout')
        ], 200);
    }

    public function me($guard = 'users')
    {
        $user = user()->fresh([
            'address',
            'lastLogin'
        ]);

        $user = $user->toLoginResponse();

        return response()->json($user, 200);
    }

    public function update(Request $request, $guard = 'users')
    {
        $user = user();

        $rules = [
            'first_name' => 'required',
            'last_name' => 'required',
            'address.line1' => 'required',
            'address.city' => 'required',
            'address.postal_code' => 'required',
            'address.country' => 'required',
            'email' => "email|unique:{$guard},email,{$user->id}",
        ];

        // Validate those rules
        $this->validate($request, $rules);

        $user->update($request->only([
            'first_name',
            'last_name',
            'email',
            'phone_number',
        ]));

        // add address to the user
        $user->updateOrCreateAddress($request->input('address'));

        if ($request->filled('avatar')) {
            $user->avatar()->sync([
                $request->input('avatar.id') => [
                    'type' => 'avatar'
                ]
            ]);
        }

        return $this->me($guard);
    }

    public function password(Request $request, $guard = 'users')
    {
        $rules = [
            'old_password' => 'required',
            'password' => 'min:6|confirmed',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        $user = user();
        if (Hash::check($request->old_password,  $user->password)) {
            $user->update([
                'password' => bcrypt($request->password)
            ]);
        } else {
            throw ValidationException::withMessages([
                'old_password' => [trans('coderstm::validation.password.old_match')],
            ]);
        }

        return response()->json([
            'message' => trans('coderstm::messages.password.changed')
        ], 200);
    }

    public function requestAccountDeletion(Request $request, $guard = 'users')
    {
        $user = user();

        $user->logs()->create([
            'type' => 'request-account-deletion',
            'message' => 'User requested deletion of their account.',
        ]);

        return $this->me($guard);
    }

    public function addDeviceToken(Request $request)
    {
        $this->validate($request, [
            'device_token' => 'required|string'
        ]);

        try {
            user()->addDeviceToken($request->device_token);
        } catch (\Exception $e) {
            //throw $e;
        }

        return response()->json([
            'message' => 'Device token added successfully.'
        ], 200);
    }
}
