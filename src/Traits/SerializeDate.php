<?php

namespace Coderstm\Traits;

use Carbon\Carbon;
use Coderstm\Coderstm;
use DateTimeInterface;

trait SerializeDate
{
    protected function serializeDate($date)
    {
        if ($date instanceof DateTimeInterface) {
            return Carbon::instance($date)->setTimezone(config('app.timezone', 'UTC'))->format(Coderstm::$dateTimeFormat);
        }

        return $date;
    }

    public function fromDateTime($value)
    {
        if (empty($value)) {
            return $value;
        }
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->utc()->format($this->getDateFormat());
        }
        if (is_numeric($value)) {
            return Carbon::createFromTimestamp($value, 'UTC')->format($this->getDateFormat());
        }
        if (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', trim($value))) {
            return $value;
        }

        return Carbon::parse($value, config('app.timezone', 'UTC'))->utc()->format($this->getDateFormat());
    }
}
