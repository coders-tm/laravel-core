<?php

namespace Coderstm\Models\Shop;

use Coderstm\Traits\Core;
use Coderstm\Models\Refund;
use Coderstm\Models\Status;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Coderstm\Models\Address;
use Coderstm\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Coderstm\Services\Resource;
use Coderstm\Traits\Statusable;
use Coderstm\Traits\OrderStatus;
use Coderstm\Models\Shop\Location;
use Illuminate\Support\Collection;
use Coderstm\Models\Shop\Order\Contact;
use Coderstm\Models\Shop\Order\TaxLine;
use Illuminate\Database\Eloquent\Model;
use Coderstm\Models\Shop\CartRepository;
use Coderstm\Models\Shop\Order\Customer;
use Coderstm\Models\Shop\Order\LineItem;
use Illuminate\Database\Eloquent\Builder;
use Coderstm\Models\Shop\Product\Inventory;
use Coderstm\Models\Shop\Order\DiscountLine;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Coderstm\Database\Factories\Shop\OrderFactory;

class Order extends Model
{
    use Core, Statusable, OrderStatus;

    const STATUS_OPEN = 'Open';
    const STATUS_PENDING = 'Pending';
    const STATUS_COMPLETED = 'Completed';
    const STATUS_CANCELLED = 'Cancelled';
    const STATUS_DECLINED = 'Declined';
    const STATUS_DISPUTED = 'Disputed';
    const STATUS_ARCHIVED = 'Archived';

    const STATUS_PAYMENT_PENDING = 'Payment pending';
    const STATUS_PAYMENT_FAILED = 'Payment failed';
    const STATUS_PAYMENT_SUCCESS = 'Payment success';
    const STATUS_PARTIALLY_PAID = 'Partially paid';
    const STATUS_PAID = 'Paid';

    const STATUS_UNFULFILLED = 'Unfulfilled';
    const STATUS_FULFILLED = 'Fulfilled';
    const STATUS_PARTIALLY_FULFILLED = 'Partially fulfilled';
    const STATUS_AWAITING_PICKUP = 'Awaiting pickup';

    const STATUS_REFUNDED = 'Refunded';
    const STATUS_PARTIALLY_REFUNDED = 'Partially refunded';

    const STATUS_RETURN_INPROGRESS = 'Return in progress';
    const STATUS_RETURNED = 'Returned';

    const STATUS_MANUAL_VERIFICATION_REQUIRED = 'Manual verification required';

    const REASON_CUSTOMER = 'Customer changed/cancelled order';
    const REASON_SIZE_TOO_SMALL = 'Size was too small';
    const REASON_SIZE_TOO_LARGE = 'Size was too large';
    const REASON_UNWANTED = 'Customer changed their mind';
    const REASON_NOT_AS_DESCRIBED = 'Item not as described';
    const REASON_WRONG_ITEM = 'Received the wrong item';
    const REASON_DEFECTIVE = 'Damaged or defective';
    const REASON_FRAUD = 'Fraudulent order';
    const REASON_INVENTORY = 'Items unavailable';
    const REASON_DECLINED = 'Payment declined';
    const REASON_UNKNOWN = 'Unknown';

    // TODO: Fix this issue of array to string converstion
    protected $logIgnore = ['options'];

    protected $fillable = [
        'location',
        'customer_id',
        'orderable_id',
        'orderable_type',
        'location_id',
        'billing_address',
        'note',
        'currency',
        'exchange_rate',
        'collect_tax',
        'options',
        'source',
        'sub_total',
        'tax_total',
        'discount_total',
        'grand_total',
        'due_date',
    ];

    protected $casts = [
        'collect_tax' => 'boolean',
        'billing_address' => 'array',
        'due_date' => 'datetime',
        'options' => 'json',
    ];

    protected $hidden = [
        'customer_id',
        'location_id',
        'orderable_id',
        'orderable_type',
    ];

    protected $with = [
        'status',
        'customer',
        'contact',
    ];

    protected $appends = [
        'total_line_items',
        'amount',
        'due_amount',
        'refundable_amount',
        'formated_id',
        'has_due',
        'has_payment',
        'can_edit',
        'can_refund',
        'is_cancelled',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function line_items()
    {
        return $this->morphMany(LineItem::class, 'itemable')->where('quantity', '>', 0);
    }

    public function tax_lines()
    {
        return $this->morphMany(TaxLine::class, 'taxable');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function status()
    {
        return $this->morphMany(Status::class, 'statusable');
    }

    public function discount()
    {
        return $this->morphOne(DiscountLine::class, 'discountable');
    }

    public function contact()
    {
        return $this->morphOne(Contact::class, 'contactable');
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function orderable()
    {
        return $this->morphTo()->withOnly([]);
    }

    public function hasDiscount(): bool
    {
        return !is_null($this->discount) ?: false;
    }

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->total(),
        );
    }

    protected function dueAmount(): Attribute
    {
        return Attribute::make(
            get: fn() => round($this->grand_total - $this->paid_total, 2),
        );
    }

    protected function refundableAmount(): Attribute
    {
        return Attribute::make(
            get: fn() => round($this->paid_total - $this->refund_total, 2),
        );
    }

    protected function formatedId(): Attribute
    {
        return Attribute::make(
            get: fn() => "#{$this->id}",
        );
    }

    protected function hasDue(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->due_amount > 0,
        );
    }

    protected function hasPayment(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->paid_total > 0,
        );
    }

    protected function totalLineItems(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->line_items_quantity) {
                    return '0 Items';
                }
                return "{$this->line_items_quantity} Item" . ($this->line_items_quantity > 1 ? 's' : '');
            },
        );
    }

    protected function isCompleted(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->hasStatus(static::STATUS_COMPLETED),
        );
    }

    protected function isCancelled(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->hasStatus(static::STATUS_CANCELLED),
        );
    }

    protected function isPaid(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->hasStatus(static::STATUS_PAID),
        );
    }

    protected function canEdit(): Attribute
    {
        return Attribute::make(
            get: fn() => !$this->hasStatus(Order::STATUS_CANCELLED) && !$this->hasStatus(Order::STATUS_COMPLETED),
        );
    }

    protected function canRefund(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->hasAnyStatus([Order::STATUS_PAID, Order::STATUS_PARTIALLY_PAID]) && !$this->hasStatus(Order::STATUS_REFUNDED),
        );
    }

    public function setLocationAttribute($location)
    {
        $this->attributes['location_id'] = has($location)->id;
    }

    public function syncLineItems(Collection $line_items, $detach = true)
    {
        if ($detach) {
            // delete removed line_items
            $this->line_items()
                ->whereNotIn('id', $line_items->pluck('id')->filter())
                ->each(function ($item) {
                    $item->delete();
                });
        }

        // update or create line_items
        foreach ($line_items as $item) {
            // update or create the product
            $product = $this->line_items()->updateOrCreate([
                'id' => has($item)->id,
            ], Arr::only($item, (new LineItem())->getFillable()));

            // update the discount
            if (!empty(has($item)->discount)) {
                $product->discount()->updateOrCreate([
                    'id' => has($item['discount'])->id,
                ], (new DiscountLine($item['discount']))->toArray());
            } else {
                $product->discount()->delete();
            }
        }
    }

    public function syncLineItemsWithoutDetach(Collection $line_items)
    {
        $this->syncLineItems($line_items, false);
    }

    public function duplicate()
    {
        $replicate = new Resource($this->replicate([
            'created_at',
            'updated_at',
            'due_date',
        ])->toArray());

        return static::modifyOrCreate($replicate);
    }

    public static function modifyOrCreate(Resource $resource)
    {
        $resource->merge([
            'customer_id' => $resource->input('customer.id') ?? $resource->customer_id,
        ]);

        $order = static::updateOrCreate([
            'id' => has($resource)->id
        ], $resource->only((new static)->getFillable()));

        $order = $order->saveRelated($resource);

        if (!$order->has_due) {
            $order->markedAsPaid();
            $order->markedAsCompleted();
        }

        return $order->fresh('status');
    }

    public function saveRelated(Resource $resource)
    {
        $cart = new CartRepository($resource->input());

        $resource->merge([
            'sub_total' => $cart->sub_total,
            'tax_lines' => $cart->tax_lines->toArray(),
            'tax_total' => $cart->tax_total,
            'discount_total' => $cart->discount_total,
            'grand_total' => $cart->grand_total,
        ]);

        $this->fill($resource->only([
            'sub_total',
            'tax_total',
            'discount_total',
            'grand_total',
        ]))->save();

        // update order line_items
        if ($resource->filled('line_items')) {
            $this->syncLineItems(collect($resource->input('line_items')));
        }

        // update customer
        if ($resource->boolean('contact.update_customer_profile') && $this->customer) {
            $this->customer->update(Arr::only($resource->contact, ['email', 'phone_number']));
        }

        // update order contact
        if ($resource->hasAny(['contact.email', 'contact.phone_number'])) {
            if ($this->contact) {
                $this->contact->update((new Contact($resource->contact))->toArray());
            } else {
                $this->contact()->save(new Contact($resource->contact));
            }
        }

        // update order discount
        if ($resource->filled('discount')) {
            if ($this->discount) {
                $this->discount->update((new DiscountLine($resource->discount))->toArray());
            } else {
                $this->discount()->save(new DiscountLine($resource->discount));
            }
        }

        // remove discount
        if ($resource->boolean('discount_removed') ?: false) {
            $this->discount()->delete();
        }

        // update order tax lines
        if ($resource->filled('tax_lines')) {
            $tax_lines = collect($resource->tax_lines);
            $this->tax_lines()->whereNotIn('id', $tax_lines->pluck('id')->filter())->delete();
            $tax_lines->each(function ($tax) {
                $this->tax_lines()->updateOrCreate([
                    'id' => has($tax)->id,
                ], $tax);
            });
        }

        // current instance
        return $this->fresh('status');
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
        $this->markedAsPaid();
        $this->markedAsCompleted();

        return $this->fresh(['payments', 'status']);
    }

    protected function adjustInventory(LineItem $lineItem, $location_id)
    {
        $inventory = Inventory::where([
            'location_id' => $location_id,
            'variant_id' => $lineItem->variant_id,
        ])->first();
        if ($inventory) {
            $inventory->setAvailable($lineItem->quantity);
        }
        return $inventory;
    }

    public function scopeSortBy($query, $column = 'CREATED_AT_ASC', $direction = 'asc')
    {
        switch ($column) {
            case 'CUSTOMER_NAME_ASC':
                $query->orderByRaw('(SELECT CONCAT(`first_name`, `first_name`) AS name FROM users WHERE users.id = orders.customer_id) ASC');
                break;

            case 'CUSTOMER_NAME_DESC':
                $query->orderByRaw('(SELECT CONCAT(`first_name`, `first_name`) AS name FROM users WHERE users.id = orders.customer_id) DESC');
                break;

            case 'CREATED_AT_DESC':
                $query->orderBy('created_at', 'desc');
                break;

            case 'CREATED_AT_ASC':
            default:
                $query->orderBy('created_at', 'asc');
                break;
        }

        return $query;
    }

    public function scopeOnlyOwner($query)
    {
        return $query->where('customer_id', user()->id);
    }

    public function scopeWhereHasStatus($query, $status)
    {
        return $query->whereHas('status', function ($q) use ($status) {
            $q->where('label', $status);
        });
    }

    public function scopeWhereInStatus($query, array $status = [])
    {
        return $query->whereHas('status', function ($q) use ($status) {
            $q->whereIn('label', $status);
        });
    }

    public function restock()
    {
        foreach ($this->line_items as $product) {
            $this->adjustInventory($product, $this->location_id);
        };
    }

    protected function formatAmount($amount)
    {
        return format_amount($amount, $this->currency);
    }

    protected function billingAddress()
    {
        return (new Address($this->billing_address ?? []))->label;
    }

    private function toPdfArray(): array
    {
        return [
            'id' => $this->formated_id,
            'currency' => $this->currency,
            'phone_number' => optional($this->contact)->phone_number,
            'customer_name' => optional($this->customer)->name ?? 'NA',
            'billing_address' => $this->billingAddress(),
            'line_items' => $this->line_items,
            'location' => optional($this->location)->address_label,
            'sub_total' => $this->formatAmount($this->sub_total),
            'tax_total' => $this->formatAmount($this->tax_total),
            'discount_total' => $this->formatAmount($this->discount_total),
            'grand_total' => $this->formatAmount($this->grand_total),
            'paid_total' => $this->formatAmount($this->paid_total),
            'due_amount' => $this->formatAmount($this->due_amount),
            'created_at' => $this->created_at->format('d-m-Y h:i a'),
        ];
    }

    public function posPdf()
    {
        return Pdf::loadView('pdfs.order-pos', $this->toPdfArray())->setPaper([0, 0, 260.00, 600.80]);
    }

    public function receiptPdf()
    {
        return Pdf::loadView('pdfs.order-receipt', $this->toPdfArray());
    }

    public function download()
    {
        return $this->receiptPdf()->download("Invoice-{$this->id}.pdf");
    }

    public function total()
    {
        return $this->formatAmount($this->grand_total);
    }

    protected function generateKey()
    {
        $key = Str::uuid();

        while (static::where('key', $key)->first()) {
            $key = Str::uuid();
        }

        $this->key = $key;
    }

    static function findByKey($key): self
    {
        return static::where('key', $key)->firstOrFail();
    }

    protected function label(): string
    {
        return $this->options?->title ?? "#{$this->id}";
    }

    public function toPublic(): array
    {
        return [
            'key' => $this->key,
            'currency' => Str::upper($this->currency),
            'label' => $this->label(),
            'line_items' => $this->line_items,
            'raw_amount' => $this->grand_total,
            'amount' => $this->total()
        ];
    }

    public function guardInvalidPayment()
    {
        if ($this->is_paid) {
            throw new \InvalidArgumentException('This invoice has already been paid.', 422);
        }

        if ($this->grand_total <= 0) {
            throw new \InvalidArgumentException('The invoice amount must be greater than zero.', 422);
        }
    }

    /**
     * Scope a query to only include paid
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->whereHas('status', function ($q) {
            $q->where('label', static::STATUS_PAID);
        });
    }

    protected static function newFactory()
    {
        return OrderFactory::new();
    }

    protected static function booted()
    {
        parent::booted();

        static::creating(function ($model) {
            $model->generateKey();

            if (empty($model->currency)) {
                $model->currency = config('cashier.currency');
            }

            if (empty($model->exchange_rate)) {
                $model->exchange_rate = 1;
            }

            if (empty($model->location)) {
                $model->location = Location::first()?->toArray();
            }
        });

        static::created(function ($model) {
            $model->attachStatus(self::STATUS_OPEN);
        });

        static::addGlobalScope('count', function (Builder $builder) {
            $builder->withSum('payments as paid_total', 'amount');
            $builder->withSum('line_items as line_items_quantity', 'quantity');
            $builder->withSum('refunds as refund_total', 'amount');
        });
    }
}
