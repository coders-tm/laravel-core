<?php

namespace Coderstm\Models\Subscription;

use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usage extends Model
{
    use HasFactory, SerializeDate;

    public $timestamps = false;

    protected $table = 'subscription_usages';

    protected $fillable = ['slug', 'used', 'subscription_id'];

    protected $casts = [];

    public function scopeByFeatureSlug($query, string $featureSlug)
    {
        return $query->whereSlug($featureSlug);
    }
}
