<?php

namespace Coderstm\Traits;

use DateTimeInterface;

trait SerializeDate
{
    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        if ($date instanceof DateTimeInterface) {
            return $date->format($this->dateTimeFormat ?? DateTimeInterface::ATOM);
        }
        return $date;
    }
}
