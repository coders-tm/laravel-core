<?php

namespace Coderstm\Models\Subscription;

use Carbon\Carbon;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usage extends Model
{
    use HasFactory, SerializeDate;

    public $timestamps = false;

    protected $table = 'subscription_usages';

    protected $fillable = ['slug', 'used', 'subscription_id', 'reset_at'];

    protected $casts = ['reset_at' => 'datetime'];

    public function scopeByFeatureSlug($query, string $featureSlug)
    {
        return $query->whereSlug($featureSlug);
    }

    public function expired(): bool
    {
        if (is_null($this->reset_at)) {
            return false;
        }

        return Carbon::now()->gte($this->reset_at);
    }
}
