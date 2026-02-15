<?php

namespace Coderstm\Traits;

use Coderstm\Coderstm;
use DateTimeInterface;

trait SerializeDate
{
    protected function serializeDate($date)
    {
        if ($date instanceof DateTimeInterface) {
            return $date->format(Coderstm::$dateTimeFormat);
        }

        return $date;
    }
}
