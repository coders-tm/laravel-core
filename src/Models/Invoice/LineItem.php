<?php

namespace Coderstm\Models\Invoice;

use Coderstm\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LineItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'invoice_line_items';

    protected $fillable = [
        'description',
        'stripe_id',
        'stripe_price',
        'stripe_plan',
        'amount',
        'quantity',
        'currency',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
