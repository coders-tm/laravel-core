<?php

namespace Coderstm\Models\Shop\Order;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxLine extends Model implements \Coderstm\Contracts\Currencyable
{
    public function getCurrencyFields(): array
    {
        return ['amount'];
    }

    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['label', 'rate', 'amount', 'type'];

    const TYPE_NORMAL = 'normal';

    const TYPE_COMPOUND = 'compound';

    protected $hidden = ['taxable_type', 'taxable_id'];

    public function taxable()
    {
        return $this->morphTo();
    }
}
