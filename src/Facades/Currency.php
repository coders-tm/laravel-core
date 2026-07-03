<?php

namespace Coderstm\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string code()
 * @method static float rate()
 * @method static float convert(float $amount)
 * @method static string format(float $amount)
 * @method static array toArray($data, array $fields)
 * @method static mixed transform($data)
 * @method static \Coderstm\Services\Currency set(string $code, float $rate)
 * @method static \Coderstm\Services\Currency initialize(?string $code = null)
 * @method static \Coderstm\Services\Currency resolve(array $address = [])
 * @method static \Coderstm\Services\Currency revert()
 * @method static bool isBase()
 * @method static string base()
 *
 * @see \Coderstm\Services\Currency
 */
class Currency extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'currency';
    }
}
