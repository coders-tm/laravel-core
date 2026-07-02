<?php

namespace Coderstm\Cashier;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use NumberFormatter;
use Stripe\StripeClient;

class Cashier
{
    protected static $formatCurrencyUsing;

    protected static $stripeClient;

    public static function stripe(array $options = []): StripeClient
    {
        $key = $options['api_key'] ?? config('stripe.secret');

        return new StripeClient($key);
    }

    public static function formatCurrencyUsing(callable $callback)
    {
        static::$formatCurrencyUsing = $callback;
    }

    public static function formatAmount(int $amount, ?string $currency = null, ?string $locale = null, array $options = []): string
    {
        if (static::$formatCurrencyUsing) {
            return call_user_func(static::$formatCurrencyUsing, $amount, $currency, $locale, $options);
        }
        $money = new Money($amount, new Currency(strtoupper($currency ?? config('stripe.currency'))));
        $locale = $locale ?? config('stripe.currency_locale', 'en_US');
        $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        if (isset($options['min_fraction_digits'])) {
            $numberFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $options['min_fraction_digits']);
        }
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, new ISOCurrencies);

        return $moneyFormatter->format($money);
    }
}
