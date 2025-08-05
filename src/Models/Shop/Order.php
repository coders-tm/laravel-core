<?php

namespace Coderstm\Models\Shop;

use Coderstm\Traits\Core;
use Coderstm\Models\Refund;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Coderstm\Models\Address;
use Coderstm\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Coderstm\Services\Resource;
use Coderstm\Traits\OrderStatus;
use Coderstm\Models\Shop\Location;
use Illuminate\Support\Collection;
use Coderstm\Models\Shop\Order\Contact;
use Coderstm\Models\Shop\Order\TaxLine;
use Coderstm\Repository\CartRepository;
use Illuminate\Database\Eloquent\Model;
use Coderstm\Contracts\PaymentInterface;
use Coderstm\Models\Shop\Order\Customer;
use Coderstm\Models\Shop\Order\LineItem;
use Illuminate\Database\Eloquent\Builder;
use Coderstm\Models\Shop\Product\Inventory;
use Coderstm\Models\Shop\Order\DiscountLine;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Coderstm\Database\Factories\Shop\OrderFactory;

class Order extends Model
{
    use Core, OrderStatus;

    // Cancellation reasons constants
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
        'shipping_address',
        'note',
        'currency',
        'exchange_rate',
        'collect_tax',
        'source',
        'sub_total',
        'shipping_total',
        'tax_total',
        'discount_total',
        'grand_total',
        'due_date',
        // Additional shop-specific fields
        'checkout_id',
        'status',
        'payment_status',
        'fulfillment_status',
        'shipped_at',
        'delivered_at',
        'tracking_number',
        'tracking_company',
        'cancelled_at',
        'metadata',
    ];

    protected $casts = [
        'collect_tax' => 'boolean',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'due_date' => 'datetime',
        'metadata' => 'array',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'sub_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    protected $hidden = [
        'customer_id',
        'location_id',
        'orderable_id',
        'orderable_type',
    ];

    protected $with = [
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
        'is_paid',
        'is_cancelled',
        'is_completed',
        'can_edit',
        'can_refund',
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
        return $this->morphMany(Payment::class, 'paymentable')
            ->where('status', Payment::STATUS_COMPLETED);
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
            get: fn() => $this->status === self::STATUS_DELIVERED,
        );
    }

    protected function isCancelled(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === self::STATUS_CANCELLED,
        );
    }

    protected function isPaid(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->payment_status === self::STATUS_PAID,
        );
    }

    protected function canEdit(): Attribute
    {
        return Attribute::make(
            get: fn() => !in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_DELIVERED]),
        );
    }

    protected function canRefund(): Attribute
    {
        return Attribute::make(
            get: fn() => in_array($this->payment_status, [self::STATUS_PAID]) &&
                !in_array($this->payment_status, [self::STATUS_REFUNDED]),
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

    public static function modifyOrCreate($resource): self
    {
        if (is_array($resource)) {
            $resource = new Resource($resource);
        }

        $resource->merge([
            'customer_id' => $resource->input('customer.id') ?? $resource->customer_id,
        ]);

        $order = static::updateOrCreate([
            'id' => has($resource)->id
        ], $resource->only((new static)->getFillable()));

        $order = $order->saveRelated($resource);

        if (!$order->has_due) {
            $order->markAsPaid();
        }

        return $order;
    }

    public function saveRelated($resource): self
    {
        if (is_array($resource)) {
            $resource = new Resource($resource);
        }

        // Check if we should preserve existing tax calculations (e.g., from checkout)
        $preserveTaxCalculations = $resource->boolean('preserve_tax_calculations', false);

        if ($preserveTaxCalculations && $resource->filled('tax_lines') && $resource->filled('tax_total')) {
            // Use the provided tax values without recalculation
            $this->fill([
                'sub_total' => $resource->sub_total,
                'tax_total' => $resource->tax_total,
                'discount_total' => $resource->discount_total ?? 0,
                'grand_total' => $resource->grand_total,
            ])->save();

            // Directly sync the tax lines without recalculation
            if ($resource->filled('tax_lines')) {
                $tax_lines = collect($resource->tax_lines);
                $this->tax_lines()->whereNotIn('id', $tax_lines->pluck('id')->filter())->delete();
                $tax_lines->each(function ($tax) {
                    $this->tax_lines()->updateOrCreate([
                        'id' => has($tax)->id,
                    ], $tax);
                });
            }
        } else {
            // Use CartRepository for calculation (existing behavior)
            $cart = new CartRepository($resource->input());

            $resource->merge([
                'sub_total' => $cart->sub_total,
                'tax_lines' => $cart->tax_lines->toArray(),
                'tax_total' => $cart->tax_total,
                'discount_total' => $cart->discount_total,
                'grand_total' => $cart->grand_total,
            ]);

            $this->fill([
                'sub_total' => $cart->sub_total,
                'tax_total' => $cart->tax_total,
                'discount_total' => $cart->discount_total,
                'grand_total' => $cart->grand_total,
            ])->save();

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
        }

        // update order line_items
        if ($resource->filled('line_items')) {
            $this->syncLineItems(collect($resource->input('line_items')));
        }

        // update order contact
        if ($resource->hasAny(['contact.email', 'contact.phone_number'])) {
            // update customer
            if ($resource->boolean('contact.update_customer_profile') && $this->customer) {
                $this->customer->update(Arr::only($resource->contact, ['email', 'phone_number']));
            }

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

        // current instance
        return $this;
    }

    /**
     * Mark order as paid
     */
    public function markAsPaid($payment = null, array $transaction = [])
    {
        return $this->updatePaymentStatus(
            $payment,
            $transaction,
            Payment::STATUS_COMPLETED,
            self::STATUS_PAID,
            self::STATUS_PROCESSING
        );
    }

    /**
     * Mark order as payment pending
     */
    public function markAsPaymentPending($payment = null, array $transaction = [])
    {
        return $this->updatePaymentStatus(
            $payment,
            $transaction,
            Payment::STATUS_PENDING,
            self::STATUS_PAYMENT_PENDING,
            self::STATUS_PENDING_PAYMENT
        );
    }

    /**
     * Mark order as payment failed
     */
    public function markAsPaymentFailed($payment = null, array $transaction = [])
    {
        return $this->updatePaymentStatus(
            $payment,
            $transaction,
            Payment::STATUS_FAILED,
            self::STATUS_PAYMENT_FAILED,
            self::STATUS_PENDING_PAYMENT
        );
    }

    /**
     * Update payment and order status in a centralized way
     */
    private function updatePaymentStatus(
        $payment,
        array $transaction,
        string $paymentStatus,
        string $orderPaymentStatus,
        string $orderStatus
    ) {
        $this->handlePaymentStatusChange($payment, $transaction, $paymentStatus);

        $this->update([
            'payment_status' => $orderPaymentStatus,
            'status' => $orderStatus,
        ]);

        return $this->fresh(['payments']);
    }

    /**
     * Handle payment creation logic for different payment statuses
     */
    private function handlePaymentStatusChange($payment, array $transaction, string $paymentStatus)
    {
        if ($payment instanceof PaymentInterface) {
            $this->createPayment($payment);
        } elseif ($payment) {
            $this->createPayment([
                'payment_method_id' => $payment,
                'transaction_id' => $transaction['id'] ?? null,
                'amount' => $transaction['amount'] ?? $this->grand_total,
                'status' => $paymentStatus,
                'note' => $transaction['note'] ?? null,
                'currency' => $transaction['currency'] ?? $this->currency,
                'gateway_response' => $transaction['gateway_response'] ?? null,
                'processed_at' => $transaction['processed_at'] ?? now(),
            ]);
        }
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
        return $query->where('customer_id', user('id'));
    }

    public function scopeWhereStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeWhereInStatus($query, array $status = [])
    {
        return $query->whereIn('status', $status);
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

    public function toPdfArray(): array
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

    public function rawAmount()
    {
        return (int) ($this->grand_total * 100);
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

    public function scopePaid($query)
    {
        return $query->where('payment_status', self::STATUS_PAID);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING_PAYMENT);
    }

    public function scopeByPaymentStatus($query, $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    public function scopeByFulfillmentStatus($query, $fulfillmentStatus)
    {
        return $query->where('fulfillment_status', $fulfillmentStatus);
    }

    public function isPendingPayment(): bool
    {
        return $this->payment_status === self::STATUS_PAYMENT_PENDING;
    }

    public function isShipped(): bool
    {
        return in_array($this->status, [self::STATUS_SHIPPED, self::STATUS_DELIVERED]);
    }

    public function markAsShipped($trackingNumber = null, $trackingCompany = null): bool
    {
        return $this->update([
            'status' => self::STATUS_SHIPPED,
            'fulfillment_status' => self::STATUS_FULFILLMENT_SHIPPED,
            'shipped_at' => now(),
            'tracking_number' => $trackingNumber,
            'tracking_company' => $trackingCompany,
        ]);
    }

    public function markAsDelivered(): bool
    {
        return $this->update([
            'status' => self::STATUS_DELIVERED,
            'fulfillment_status' => self::STATUS_FULFILLMENT_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    public function cancel($reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
            'fulfillment_status' => self::STATUS_FULFILLMENT_CANCELLED,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);
    }

    public function markAsCompleted()
    {
        return $this->markedAsCompleted();
    }

    public function markAsOpen()
    {
        return $this->markedAsOpen();
    }

    /**
     * Get shipping address as string
     */
    protected function shippingAddress()
    {
        return (new Address($this->shipping_address ?? []))->label;
    }

    /**
     * Create payment for this order
     */
    public function createPayment($attributes = [])
    {
        if ($attributes instanceof PaymentInterface) {
            // Convert PaymentInterface to array
            $attributes = $attributes->toArray();
        }

        return Payment::createForOrder($this, $attributes);
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

            if (empty($model->status)) {
                $model->status = self::STATUS_PENDING;
            }

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

        static::addGlobalScope('count', function (Builder $builder) {
            $builder->withSum('payments as paid_total', 'amount');
            $builder->withSum('line_items as line_items_quantity', 'quantity');
            $builder->withSum('refunds as refund_total', 'amount');
        });
    }
}
