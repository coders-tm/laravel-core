<?php

namespace Coderstm\Models\Subscription;

use Coderstm\Coderstm;
use Coderstm\Traits\Core;
use Illuminate\Support\Str;
use Coderstm\Models\Address;
use Coderstm\Models\Payment;
use InvalidArgumentException;
use Barryvdh\DomPDF\Facade\Pdf;
use Coderstm\Models\Shop\Order;
use Coderstm\Services\Resource;
use Coderstm\Models\Subscription;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Coderstm\Repository\InvoiceRepository;
use Coderstm\Models\Subscription\Invoice\User;
use Coderstm\Models\Subscription\Invoice\TaxLine;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Coderstm\Models\Subscription\Invoice\LineItem;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Coderstm\Models\Subscription\Invoice\DiscountLine;

class Invoice extends Model
{
    use Core;

    const STATUS_OPEN = 'Open';
    const STATUS_CANCELLED = 'Cancelled';

    const STATUS_PAYMENT_PENDING = 'Payment pending';
    const STATUS_PAYMENT_FAILED = 'Payment failed';
    const STATUS_PAID = 'Paid';

    protected $table = 'subscription_invoices';

    protected $fillable = [
        'user_id',
        'due_date',
        'subscription_id',
        'billing_address',
        'note',
        'status',
        'currency',
        'exchange_rate',
        'collect_tax',
        'sub_total',
        'tax_total',
        'discount_total',
        'grand_total',
    ];

    protected $dateTimeFormat = 'd M, Y \a\t h:i a';

    protected $casts = [
        'collect_tax' => 'boolean',
        'billing_address' => 'array',
        'due_date' => 'datetime',
    ];

    protected $hidden = [
        'user_id',
    ];

    protected $with = [
        'user',
        'line_items',
        'tax_lines',
        'discount',
    ];

    protected $appends = [
        'amount',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Coderstm::$subscriptionModel);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function line_items()
    {
        return $this->hasMany(LineItem::class);
    }

    public function tax_lines()
    {
        return $this->morphMany(TaxLine::class, 'taxable');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function discount()
    {
        return $this->morphOne(DiscountLine::class, 'discountable');
    }

    public function hasDiscount(): bool
    {
        return !is_null($this->discount) ?: false;
    }

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->total(),
        );
    }

    protected function dueAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->grand_total - $this->paid_total,
        );
    }

    public function hasDue()
    {
        return $this->due_amount > 0;
    }

    public function syncLineItems(Collection $line_items)
    {
        // delete removed line_items
        $this->line_items()
            ->whereNotIn('id', $line_items->pluck('id')->filter())
            ->each(function ($item) {
                $item->delete();
            });

        // update or create line_items
        foreach ($line_items as $item) {
            $this->line_items()->updateOrCreate([
                'id' => has($item)->id,
            ], $item);
        }
    }

    public function markAsPaid($paymentMethod, array $transaction = [])
    {
        $transaction = optional((object) $transaction);

        $this->payments()->updateOrCreate([
            'payment_method_id' => $paymentMethod,
            'transaction_id' => $transaction->id,
        ], [
            'amount' => $transaction->amount ?? $this->due_amount,
            'status' => $transaction->status ?? 'success',
            'note' => $transaction->note,
        ]);

        // marked order status as paid
        $this->setStatusPaid();
        $this->save();

        return $this->fresh(['payments']);
    }

    public function scopeOnlyOwner($query)
    {
        return $query->where('user_id', user()->id);
    }

    protected function formatAmount($amount)
    {
        return format_amount($amount, $this->currency);
    }

    protected function billingAddress()
    {
        return (new Address($this->billing_address ?? []))->label;
    }

    protected function toPdfArray(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'phone_number' => optional($this->user)->phone_number,
            'user_name' => optional($this->user)->name ?? 'NA',
            'line_items' => $this->line_items,
            'billing_address' => $this->billingAddress(),
            'location' => optional($this->location)->address_label,
            'sub_total' => $this->formatAmount($this->sub_total),
            'tax_total' => $this->formatAmount($this->tax_total),
            'discount_total' => $this->formatAmount($this->discount_total),
            'grand_total' => $this->formatAmount($this->grand_total),
            'paid_total' => $this->formatAmount($this->paid_total),
            'due_amount' => $this->formatAmount($this->due_amount),
            'created_at' => $this->created_at->format($this->dateTimeFormat),
        ];
    }

    public function posPdf()
    {
        return Pdf::loadView('coderstm::pdfs.invoice-pos', $this->toPdfArray())->setPaper([0, 0, 260.00, 600.80]);
    }

    public function receiptPdf()
    {
        return Pdf::loadView('coderstm::pdfs.invoice-receipt', $this->toPdfArray());
    }

    public function download()
    {
        return $this->receiptPdf()->download("Invoice-{$this->number}.pdf");
    }

    public function setStatusOpen()
    {
        $this->status = static::STATUS_OPEN;
    }

    public function setStatusCancelled()
    {
        $this->status = static::STATUS_CANCELLED;
    }

    public function setStatusPaid()
    {
        $this->status = static::STATUS_PAID;
    }

    public function setStatusPaymentPending()
    {
        $this->status = static::STATUS_PAYMENT_PENDING;
    }

    public function setStatusPaymentFailed()
    {
        $this->status = static::STATUS_PAYMENT_FAILED;
    }

    public function total()
    {
        return $this->formatAmount($this->grand_total);
    }

    public function order(): MorphOne
    {
        return $this->morphOne(Order::class, 'orderable');
    }

    static function findByKey($key): self
    {
        return static::where('key', $key)->firstOrFail();
    }

    protected function generateNumber()
    {
        $number = strtoupper(fake()->regexify('[A-F0-9]{8}'));

        while (static::where('number', $number)->first()) {
            $number = strtoupper(fake()->regexify('[A-F0-9]{8}'));
        }

        $this->number = $number;
    }

    protected function generateKey()
    {
        $key = Str::uuid();

        while (static::where('key', $key)->first()) {
            $key = Str::uuid();
        }

        $this->key = $key;
    }

    static public function modifyOrCreate(Resource $resource): self
    {
        $invoice = static::updateOrCreate([
            'id' => $resource->id
        ], $resource->only((new static)->getFillable()));

        $repo = new InvoiceRepository($resource->input());

        $resource->merge([
            'sub_total' => $repo->sub_total,
            'tax_lines' => $repo->tax_lines->toArray(),
            'tax_total' => $repo->tax_total,
            'discount_total' => $repo->discount_total,
            'grand_total' => $repo->grand_total,
        ]);

        // update order line_items
        if ($resource->filled('line_items')) {
            $invoice->syncLineItems(collect($resource->input('line_items')));
        }

        // remove discount
        if ($resource->boolean('discount_removed')) {
            $invoice->discount()->delete();
        } else {
            // update order discount
            if ($resource->filled('discount')) {
                if ($invoice->discount) {
                    $invoice->discount->update((new DiscountLine($resource->discount))->toArray());
                } else {
                    $invoice->discount()->save(new DiscountLine($resource->discount));
                }
            }
        }

        // update order tax lines
        if ($resource->filled('tax_lines')) {
            $invoice->tax_lines()->whereNotIn('id', collect($resource->tax_lines)->pluck('id')->filter())->delete();

            foreach ($resource->tax_lines as $tax) {
                $invoice->tax_lines()->updateOrCreate([
                    'id' => has($tax)->id,
                ], $tax);
            }
        }

        if (!$invoice->hasDue()) {
            $invoice->setStatusPaid();
        }

        $invoice->fill($resource->only([
            'sub_total',
            'tax_total',
            'discount_total',
            'grand_total',
        ]))->save();

        $invoice->generateOrder($resource);

        // current instance
        return $invoice;
    }

    public function generateOrder(Resource $resource): Order
    {
        return Order::modifyOrCreate($resource->merge([
            'source' => 'Membership',
            'customer_id' => $this->user_id,
            'orderable_id' => $this->id,
            'orderable_type' => static::class,
        ]));
    }

    public function isPaid(): bool
    {
        return $this->status === static::STATUS_PAID;
    }

    public function guardInvalidPayment()
    {
        if ($this->isPaid()) {
            throw new InvalidArgumentException('This invoice has already been paid.', 422);
        }

        if ($this->grand_total <= 0) {
            throw new InvalidArgumentException('The invoice amount must be greater than zero.', 422);
        }
    }

    public function paymentConfirmation(Order $order)
    {
        $this->payments()->saveMany($order->payments->map(function ($payment) {
            return $payment->replicate([
                'paymentable_type',
                'paymentable_id',
            ]);
        }));

        // marked order status as paid
        $this->setStatusPaid();
        $this->save();

        // making subscription status as active
        $this->subscription->update([
            'status' => Subscription::STATUS_ACTIVE
        ]);;
    }

    /**
     * Scope a query to only include paid
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->whereStatus(static::STATUS_PAID);
    }


    protected static function booted()
    {
        parent::booted();
        static::creating(function ($model) {
            if (empty($model->currency)) {
                $model->currency = config('cashier.currency');
            }

            if (empty($model->exchange_rate)) {
                $model->exchange_rate = 1;
            }

            $model->generateNumber();
            $model->generateKey();
            $model->setStatusOpen();
        });
        static::addGlobalScope('count', function (Builder $builder) {
            $builder->withSum('payments as paid_total', 'amount');
            $builder->withMax('order as order_key', 'key');
        });
    }
}
