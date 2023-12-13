<?php

namespace Coderstm\Models\Plan;

use Carbon\Carbon;
use Coderstm\Models\Plan;
use Coderstm\Services\Period;
use Coderstm\Traits\SerializeDate;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Feature extends Model
{
    use HasFactory, HasSlug, SerializeDate;

    protected $table = 'plan_features';

    protected $fillable = [
        'label',
        'slug',
        'plan_id',
        'description',
        'value',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('label')
            ->doNotGenerateSlugsOnUpdate()
            ->allowDuplicateSlugs()
            ->saveSlugsTo('slug');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function getResetDate(Carbon $dateFrom, string $interval = 'month'): Carbon
    {
        $period = new Period($interval, 1, $dateFrom ?? now());
        return $period->getEndDate();
    }
}
