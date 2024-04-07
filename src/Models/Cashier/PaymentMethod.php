<?php

namespace Coderstm\Models\Cashier;

use Coderstm\Traits\Logable;
use Laravel\Cashier\Cashier;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use Logable, SerializeDate;

    protected $table = 'stripe_payment_methods';

    protected $fillable = [
        'stripe_id',
        'name',
        'card',
        'brand',
        'card_number',
        'exp_date',
        'is_default',
        'last_four_digit'
    ];

    protected $casts = [
        'card' => 'json',
        'is_default' => 'boolean',
    ];

    public function markAsDefault()
    {
        static::whereNotNull('is_default')->update([
            'is_default' => false
        ]);

        $this->update([
            'is_default' => true
        ]);

        return $this;
    }
}
