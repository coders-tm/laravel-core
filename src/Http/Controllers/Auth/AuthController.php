<?php

namespace Coderstm\Http\Controllers\Auth;

use Coderstm\Coderstm;
use Coderstm\Enum\AppStatus;
use Coderstm\Events\UserSubscribed;
use Coderstm\Http\Controllers\Controller;
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
        $request->validate(['email' => "required|email|exists:{$guard},email", 'password' => 'required', 'device_id' => 'required_if:token,true|string'], ['email.required' => __('An email address is required.'), 'email.exists' => __('Your email address doens\'t exists.')]);
        $token = $request->boolean('token', false);
        if (Auth::guard($guard)->attempt($request->only(['email', 'password']), $request->boolean('remember'))) {
            $user = $request->user($guard);
            if (! $user->is_active) {
                throw ValidationException::withMessages(['email' => [__('Your account has been disabled and cannot access this application. Please contact with admin.')]]);
            }
            try {
                $loginLog = $user->logs()->create(['type' => 'login', 'options' => Helpers::location()]);
                $user->notify(new UserLogin($loginLog));
            } catch (\Throwable $e) {
                $user->logs()->create(['type' => 'login-alert', 'status' => Log::STATUS_ERROR, 'message' => $e->getMessage()]);
            }
            if (! $token) {
                return response()->json($user->toLoginResponse(), 200);
            } else {
                Auth::guard($guard)->logout();
            }
            $user->tokens()->where('name', $request->device_id)->delete();
            $token = $user->createToken($request->device_id, [$guard]);

            return response()->json(['user' => $user->toLoginResponse(), 'token' => $token->plainTextToken], 200);
        } else {
            throw ValidationException::withMessages(['password' => [__('Your password doesn\'t match with our records.')]]);
        }
    }

    public function signup(Request $request, $guard = 'users')
    {
        $rules = ['email' => 'required|email|unique:users', 'first_name' => 'required', 'last_name' => 'required', 'phone_number' => 'required', 'line1' => 'required', 'city' => 'required', 'postal_code' => 'required', 'country' => 'required', 'password' => 'required|min:6|confirmed', 'device_id' => 'required_if:token,true|string'];
        $this->validate($request, $rules);
        $token = $request->boolean('token', false);
        $request->merge(['password' => Hash::make($request->password), 'status' => AppStatus::PENDING->value]);
        $user = Coderstm::$userModel::create($request->only(['email', 'first_name', 'last_name', 'company_name', 'email', 'phone_number', 'password', 'status']));
        $user->updateOrCreateAddress($request->input());
        event(new UserSubscribed($user));
        if (! $token) {
            Auth::guard($guard)->login($user);

            return response()->json($user->toLoginResponse(), 200);
        }
        $token = $user->createToken($request->device_id, [$guard]);

        return response()->json(['user' => $user, 'token' => $token->plainTextToken], 200);
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

        return response()->json(['message' => __('You have been successfully logged out!')], 200);
    }

    public function me($guard = 'users')
    {
        $user = request()->user($guard);
        if (! $user) {
            $user = request()->user('sanctum');
        }
        $user = $user->loadMissing(['address']);
        $user = $user->toLoginResponse();

        return response()->json($user, 200);
    }

    public function update(Request $request, $guard = 'users')
    {
        $user = user();
        $rules = ['first_name' => 'required', 'last_name' => 'required', 'address.line1' => 'required', 'address.city' => 'required', 'address.postal_code' => 'required', 'address.country' => 'required', 'email' => "email|unique:{$guard},email,{$user->id}"];
        $this->validate($request, $rules);
        $user->update($request->only(['first_name', 'last_name', 'email', 'phone_number']));
        $user->updateOrCreateAddress($request->input('address'));
        if ($request->filled('avatar')) {
            $user->avatar()->sync([$request->input('avatar.id') => ['type' => 'avatar']]);
        }

        return $this->me($guard);
    }

    public function password(Request $request, $guard = 'users')
    {
        $rules = ['old_password' => 'required', 'password' => 'min:6|confirmed'];
        $this->validate($request, $rules);
        $user = user();
        if (Hash::check($request->old_password, $user->password)) {
            $user->update(['password' => bcrypt($request->password)]);
        } else {
            throw ValidationException::withMessages(['old_password' => [__('Old password doesn\'t match!')]]);
        }

        return response()->json(['message' => __('Password has been changed successfully!')], 200);
    }

    public function requestAccountDeletion(Request $request, $guard = 'users')
    {
        $user = user();
        $user->logs()->create(['type' => 'request-account-deletion', 'message' => __('User requested deletion of their account.')]);

        return $this->me($guard);
    }

    public function addDeviceToken(Request $request)
    {
        $this->validate($request, ['device_token' => 'required|string']);
        try {
            user()->addDeviceToken($request->device_token);
        } catch (\Throwable $e) {
        }

        return response()->json(['message' => __('Device token added successfully.')], 200);
    }
}
