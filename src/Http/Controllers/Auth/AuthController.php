<?php

namespace Coderstm\Http\Controllers\Auth;

use Coderstm\Coderstm;
use Coderstm\Enum\AppStatus;
use Coderstm\Events\UserSubscribed;
use Coderstm\Http\Controllers\Controller;
use Coderstm\Models\Admin;
use Coderstm\Models\Log;
use Coderstm\Notifications\UserLogin;
use Coderstm\Services\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request, $guard = 'users')
    {
        $request->validate(
            [
                'email' => "required|email|exists:{$guard},email",
                'password' => 'required',
                'device_id' => 'required_if:token,true|string', // device_id is required if token is true
            ],
            [
                'email.required' => __('An email address is required.'),
                'email.exists' => __('Your email address doens\'t exists.'),
            ]
        );

        $token = $request->boolean('token', false);

        // Attempt to log the user in
        if (Auth::guard($guard)->attempt($request->only(['email', 'password']), $request->boolean('remember'))) {
            // Get the authenticated user
            /** @var Admin $user */
            $user = $request->user($guard);

            // check user status
            if (! $user->is_active) {
                throw ValidationException::withMessages([
                    'email' => [__('Your account has been disabled and cannot access this application. Please contact with admin.')],
                ]);
            }

            try {
                // Create log for user login
                $loginLog = $user->logs()->create([
                    'type' => 'login',
                    'options' => Helpers::location(),
                ]);

                // Send login alert to user if smtp configured
                $user->notify(new UserLogin($loginLog));
            } catch (\Throwable $e) {
                // Create error log if login alert fails
                $user->logs()->create([
                    'type' => 'login-alert',
                    'status' => Log::STATUS_ERROR,
                    'message' => $e->getMessage(),
                ]);
            }

            if (! $token) {
                // Response with user data for session based login
                return response()->json($user->toLoginResponse(), 200);
            } else {
                // Logout user from session guard
                Auth::guard($guard)->logout();
            }

            // Delete old token with requested device
            $user->tokens()->where('name', $request->device_id)->delete();

            // Create and return user with token
            $token = $user->createToken($request->device_id, [$guard]);

            return response()->json([
                'user' => $user->toLoginResponse(),
                'token' => $token->plainTextToken,
            ], 200);
        } else {
            throw ValidationException::withMessages([
                'password' => [__('Your password doesn\'t match with our records.')],
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
            'device_id' => 'required_if:token,true|string', // device_id is required if token is true
        ];

        // Validate those rules
        $this->validate($request, $rules);

        $token = $request->boolean('token', false);

        $request->merge([
            'password' => Hash::make($request->password),
            'status' => AppStatus::PENDING->value,
        ]);

        // create the user
        $user = Coderstm::$userModel::create($request->only([
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

        if (! $token) {
            // Response with user data for session based login
            Auth::guard($guard)->login($user);

            return response()->json($user->toLoginResponse(), 200);
        }

        // create and return user with token
        $token = $user->createToken($request->device_id, [$guard]);

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
        ], 200);
    }

    public function logout(Request $request, $guard = 'users')
    {
        try {
            $request->user()?->currentAccessToken()?->delete();
        } catch (\Throwable $th) {
            report($th);
        }

        Auth::guard($guard)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => __('You have been successfully logged out!'),
        ], 200);
    }

    public function me($guard = 'users')
    {
        $user = request()->user($guard);

        if (! $user) {
            $user = request()->user('sanctum');
        }

        $user = $user->loadMissing([
            'address',
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
                    'type' => 'avatar',
                ],
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
        if (Hash::check($request->old_password, $user->password)) {
            $user->update([
                'password' => bcrypt($request->password),
            ]);
        } else {
            throw ValidationException::withMessages([
                'old_password' => [__('Old password doesn\'t match!')],
            ]);
        }

        return response()->json([
            'message' => __('Password has been changed successfully!'),
        ], 200);
    }

    public function requestAccountDeletion(Request $request, $guard = 'users')
    {
        $user = user();

        $user->logs()->create([
            'type' => 'request-account-deletion',
            'message' => __('User requested deletion of their account.'),
        ]);

        return $this->me($guard);
    }

    public function addDeviceToken(Request $request)
    {
        $this->validate($request, [
            'device_token' => 'required|string',
            'app_id' => 'nullable|string',
        ]);

        try {
            user()->addDeviceToken($request->device_token, $request->app_id);
        } catch (\Throwable $e) {
            // throw $e;
        }

        return response()->json([
            'message' => __('Device token added successfully.'),
        ], 200);
    }
}
