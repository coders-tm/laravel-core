<?php

namespace Coderstm\Models\Plan;

use Carbon\Carbon;
use Coderstm\Coderstm;
use Coderstm\Services\Period;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Feature extends Model
{
    use HasFactory, SerializeDate;

    protected $table = 'plan_features';

    protected $fillable = [
        'slug',
        'plan_id',
        'value',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Coderstm::$planModel, 'plan_id');
    }

    public function getResetDate(Carbon $dateFrom, string $interval = 'month'): Carbon
    {
        $period = new Period($interval, 1, $dateFrom ?? now());
        return $period->getEndDate();
    }
}
