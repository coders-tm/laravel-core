<?php

namespace Coderstm\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Http;

class ReCaptchaRule implements Rule
{
    const URL = 'https://www.google.com/recaptcha/api/siteverify';

    const BOT_SCORE = 0.0;

    public function passes($attribute, $value)
    {
        $response = Http::asForm()->post(static::URL, ['secret' => config('recaptcha.secret_key'), 'response' => $value, 'remoteip' => request()->ip()])->json();

        return $response['success'] === true && $response['score'] > static::BOT_SCORE ?? false;
    }

    public function message()
    {
        return __('The verification process for reCAPTCHA failed. Please attempt again.');
    }
}
