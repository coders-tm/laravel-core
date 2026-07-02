<?php

namespace Coderstm\Services;

use Carbon\Carbon;

class Period
{
    protected $start;

    protected $end;

    protected $interval;

    protected $period = 1;

    public function __construct($interval = 'month', $count = 1, $start = '')
    {
        $this->interval = $interval;
        if (empty($start)) {
            $this->start = Carbon::now();
        } elseif (! $start instanceof Carbon) {
            $this->start = new Carbon($start);
        } else {
            $this->start = $start;
        }
        $this->period = (int) $count;
        $start = clone $this->start;
        $method = 'add'.ucfirst($this->interval).'s';
        $this->end = $start->{$method}($this->period);
    }

    public function getStartDate(): Carbon
    {
        return $this->start;
    }

    public function getEndDate(): Carbon
    {
        return $this->end;
    }

    public function getInterval(): string
    {
        return $this->interval;
    }

    public function getIntervalCount(): int
    {
        return $this->period;
    }
}
