<?php

namespace Coderstm\Models\Subscription\Invoice;

use Illuminate\Database\Eloquent\Model;
use Coderstm\Models\Subscription\Invoice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LineItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'subscription_invoice_line_items';

    protected $fillable = [
        'title',
        'description',
        'plan_id',
        'quantity',
        'price',
        'total',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
