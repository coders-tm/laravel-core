<?php

namespace Coderstm\Services;

use Coderstm\Models\Shop\ExchangeRate;

class Currency
{
    protected string $code;

    protected float $rate;

    public function __construct()
    {
        $this->code = config('app.currency', 'USD');
        $this->rate = 1.0;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function rate(): float
    {
        return $this->rate;
    }

    public function convert(float $amount): float
    {
        return round($amount * $this->rate, 2);
    }

    public function format(float $amount): string
    {
        $converted = $this->convert($amount);

        return format_amount($converted, $this->code);
    }

    public function toArray($data, array $fields): array
    {
        $result = ['currency' => $this->code];
        foreach ($fields as $field) {
            $value = is_array($data) ? $data[$field] ?? null : $data->{$field} ?? null;
            if ($value !== null) {
                $result[$field] = $this->convert((float) $value);
            }
        }

        return array_merge($data->toArray(), $result);
    }

    public function transform($data)
    {
        if (is_iterable($data) && ! $data instanceof \Coderstm\Contracts\Currencyable) {
            $isCollection = $data instanceof \Illuminate\Support\Collection;
            $transformed = [];
            foreach ($data as $key => $item) {
                $transformed[$key] = $this->transformSingle($item);
            }

            return $isCollection ? collect($transformed) : $transformed;
        }

        return $this->transformSingle($data);
    }

    protected function transformSingle($item): array
    {
        if ($item instanceof \Coderstm\Contracts\Currencyable) {
            return $this->toArray($item, $item->getCurrencyFields());
        }
        $array = is_array($item) ? $item : (method_exists($item, 'toArray') ? $item->toArray() : (array) $item);

        return array_merge($array, ['currency' => $this->code]);
    }

    public function set(string $code, float $rate): self
    {
        $this->code = strtoupper($code);
        $this->rate = (float) $rate;

        return $this;
    }

    public function initialize(?string $code = null): self
    {
        $baseCode = self::base();
        $code = strtoupper($code ?? $baseCode);
        if ($code === $baseCode) {
            return $this->set($baseCode, 1.0);
        }
        try {
            $rate = \Coderstm\Models\Shop\ExchangeRate::rateFor($code);

            return $this->set($code, $rate);
        } catch (\Throwable $e) {
            return $this->set($baseCode, 1.0);
        }
    }

    public function resolve(array $address = []): self
    {
        $currencyCode = null;
        if (! empty($address['country_code'])) {
            $currencyCode = ExchangeRate::getCurrencyFromCountryCode($address['country_code']);
        }
        if (! $currencyCode && ! empty($address['country'])) {
            $currencyCode = ExchangeRate::getCurrencyFromCountry($address['country']);
        }
        if (! $currencyCode) {
            $cfCountry = request()->header('CF-IPCountry');
            if ($cfCountry && $cfCountry !== 'XX') {
                $currencyCode = ExchangeRate::getCurrencyFromCountryCode($cfCountry);
            }
        }
        if (! $currencyCode) {
            $countryCode = request()->ipLocation('countryCode');
            if ($countryCode) {
                $currencyCode = ExchangeRate::getCurrencyFromCountryCode($countryCode);
            }
        }

        return $this->initialize($currencyCode);
    }

    public function revert(): self
    {
        return $this->set(self::base(), 1.0);
    }

    public function isBase(): bool
    {
        return $this->code === self::base();
    }

    public static function base(): string
    {
        return strtoupper(config('app.currency', 'USD'));
    }
}
