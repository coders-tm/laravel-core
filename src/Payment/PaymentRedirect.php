<?php

namespace Coderstm\Payment;

use Coderstm\Models\Payment;
use Illuminate\Http\RedirectResponse;

class PaymentRedirect
{
    /**
     * Redirect to return_url (deep link or external URL) if configured in payment metadata,
     * otherwise fallback to standard application redirect URL with flash message.
     */
    public static function to(
        Payment|string|null $payment = null,
        string $provider = '',
        string $fallbackUrl = '/',
        string $status = 'succeeded',
        ?string $message = null,
        string $messageType = 'success'
    ): RedirectResponse {
        if (is_string($payment)) {
            $payment = Payment::where('uuid', $payment)->first();
        } elseif ($payment === null && $state = request('state')) {
            $payment = Payment::where('uuid', $state)->first();
        }

        if ($returnUrl = ($payment?->metadata['return_url'] ?? null)) {
            $order = $payment?->paymentable;
            $token = $order?->key ?? $order?->id ?? $payment?->uuid;

            $params = array_filter([
                'success' => $status === 'succeeded' ? 'true' : 'false',
                'status' => $status,
                'provider' => $provider,
                'order_id' => $token,
                'token' => $token,
                'transaction_id' => $status === 'succeeded' ? $payment?->transaction_id : null,
                'message' => $message,
            ]);
            $separator = str_contains($returnUrl, '?') ? '&' : '?';

            return redirect($returnUrl . $separator . http_build_query($params));
        }

        $redirect = redirect($fallbackUrl);
        if ($message !== null) {
            $redirect->with($messageType, $message);
        }

        return $redirect;
    }

    /**
     * Shortcut for successful payment redirect
     */
    public static function success(
        Payment $payment,
        string $provider = '',
        string $fallbackUrl = '/',
        ?string $message = null
    ): RedirectResponse {
        return self::to(
            payment: $payment,
            provider: $provider,
            fallbackUrl: $fallbackUrl,
            status: 'succeeded',
            message: $message,
            messageType: 'success'
        );
    }

    /**
     * Shortcut for cancelled payment redirect
     */
    public static function cancel(
        string $provider = '',
        string $fallbackUrl = '/',
        ?string $message = null
    ): RedirectResponse {
        return self::to(
            payment: null,
            provider: $provider,
            fallbackUrl: $fallbackUrl,
            status: 'cancelled',
            message: $message,
            messageType: 'info'
        );
    }

    /**
     * Shortcut for failed or errored payment redirect
     */
    public static function failed(
        string $provider = '',
        string $fallbackUrl = '/',
        ?string $message = null
    ): RedirectResponse {
        return self::to(
            payment: null,
            provider: $provider,
            fallbackUrl: $fallbackUrl,
            status: 'failed',
            message: $message,
            messageType: 'error'
        );
    }

    /**
     * Alias for failed()
     */
    public static function error(
        string $provider = '',
        string $fallbackUrl = '/',
        ?string $message = null
    ): RedirectResponse {
        return self::failed($provider, $fallbackUrl, $message);
    }
}
