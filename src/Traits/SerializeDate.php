<?php

namespace Coderstm\Traits;

use Coderstm\Coderstm;
use DateTimeInterface;

trait SerializeDate
{
    /**
     * Prepare a DateTime instance for array/JSON serialization.
     * Converts the DateTime instance to a string using the specified format.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        // Check if the provided date is an instance of DateTimeInterface
        if ($date instanceof DateTimeInterface) {
            // Format the date according to the format defined in Coderstm::$dateTimeFormat
            return $date->format(Coderstm::$dateTimeFormat);
        }

        // Return the date as-is if it's not a DateTimeInterface instance
        return $date;
    }
}
