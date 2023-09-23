<?php

namespace Coderstm\Http\Controllers\Auth;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Coderstm\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\PasswordBroker;

class ForgotPasswordController extends Controller
{
    public function request(Request $request, $guard = null)
    {
        $request->validate([
            'email' => "required|email|exists:{$guard},email",
        ]);

        $status = Password::broker($guard)->sendResetLink($request->only('email'));
        if ($status === Password::INVALID_USER) {
            return response()->json([
                'message' => trans('coderstm::messages.invalid_user')
            ], 403);
        } elseif ($status === PasswordBroker::RESET_THROTTLED) {
            return response()->json([
                'message' => trans('coderstm::messages.reset_throttled')
            ], 403);
        }

        return response()->json([
            'status' => $status,
            'message' => trans('coderstm::messages.reset_email_sent')
        ], 200);
    }

    public function reset(Request $request, $guard = null)
    {
        $request->validate(
            [
                'password' => 'required|min:6|same:password_confirmation',
                'password_confirmation' => 'required'
            ],
            [
                'password_confirmation.required' => trans('coderstm::validation.password.confirmation')
            ]
        );

        $status = Password::broker($guard)->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                $user->setRememberToken(Str::random(60));

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => trans('coderstm::messages.invalid_token')
            ], 403);
        }

        return response()->json([
            'status' => $status,
            'message' => trans('coderstm::messages.password.reset')
        ], 200);
    }
}
