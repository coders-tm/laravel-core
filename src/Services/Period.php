<?php

namespace Coderstm\Services;

use Carbon\Carbon;

class Period
{
    /**
     * Starting date of the period.
     *
     * @var \Carbon\Carbon
     */
    protected $start;

    /**
     * Ending date of the period.
     *
     * @var \Carbon\Carbon
     */
    protected $end;

    /**
     * Interval.
     *
     * @var string
     */
    protected $interval;

    /**
     * Interval count.
     *
     * @var int
     */
    protected $period = 1;

    /**
     * Create a new Period instance.
     *
     * @param string $interval
     * @param int    $count
     * @param string $start
     *
     * @return void
     */
    public function __construct($interval = 'month', $count = 1, $start = '')
    {
        $this->interval = $interval;

        if (empty($start)) {
            $this->start = Carbon::now();
        } elseif (!$start instanceof Carbon) {
            $this->start = new Carbon($start);
        } else {
            $this->start = $start;
        }

        $this->period = (int) $count;
        $start = clone $this->start;
        $method = 'add' . ucfirst($this->interval) . 's';
        $this->end = $start->{$method}($this->period);
    }

    /**
     * Get start date.
     *
     * @return \Carbon\Carbon
     */
    public function getStartDate(): Carbon
    {
        return $this->start;
    }

    /**
     * Get end date.
     *
     * @return \Carbon\Carbon
     */
    public function getEndDate(): Carbon
    {
        return $this->end;
    }

    /**
     * Get period interval.
     *
     * @return string
     */
    public function getInterval(): string
    {
        return $this->interval;
    }

    /**
     * Get period interval count.
     *
     * @return int
     */
    public function getIntervalCount(): int
    {
        return $this->period;
    }
}
