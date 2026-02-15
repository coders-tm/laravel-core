<?php

namespace Coderstm\Models\Shop;

use Barryvdh\DomPDF\Facade\Pdf;
use Coderstm\Contracts\PaymentInterface;
use Coderstm\Database\Factories\Shop\OrderFactory;
use Coderstm\Models\Address;
use Coderstm\Models\Payment;
use Coderstm\Models\Refund;
use Coderstm\Models\Shop\Order\Contact;
use Coderstm\Models\Shop\Order\Customer;
use Coderstm\Models\Shop\Order\DiscountLine;
use Coderstm\Models\Shop\Order\LineItem;
use Coderstm\Models\Shop\Order\TaxLine;
use Coderstm\Models\Shop\Product\Inventory;
use Coderstm\Repository\CartRepository;
use Coderstm\Services\Resource;
use Coderstm\Traits\Core;
use Coderstm\Traits\HasRefunds;
use Coderstm\Traits\OrderStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Order extends Model implements \Coderstm\Contracts\Currencyable
{
    use Core, HasRefunds, OrderStatus;

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

    protected $logIgnore = ['options'];

    protected $fillable = ['location', 'customer_id', 'orderable_id', 'orderable_type', 'location_id', 'billing_address', 'shipping_address', 'note', 'collect_tax', 'source', 'sub_total', 'shipping_total', 'tax_total', 'discount_total', 'grand_total', 'paid_total', 'refund_total', 'line_items_quantity', 'due_date', 'checkout_id', 'status', 'payment_status', 'fulfillment_status', 'shipped_at', 'delivered_at', 'tracking_number', 'tracking_company', 'cancelled_at', 'metadata'];

    protected $casts = ['collect_tax' => 'boolean', 'billing_address' => 'array', 'shipping_address' => 'array', 'due_date' => 'datetime', 'metadata' => 'array', 'shipped_at' => 'datetime', 'delivered_at' => 'datetime', 'cancelled_at' => 'datetime', 'sub_total' => 'decimal:2', 'tax_total' => 'decimal:2', 'shipping_total' => 'decimal:2', 'discount_total' => 'decimal:2', 'grand_total' => 'decimal:2', 'paid_total' => 'decimal:2', 'refund_total' => 'decimal:2', 'line_items_quantity' => 'integer'];

    protected $hidden = ['customer_id', 'location_id', 'orderable_id', 'orderable_type'];

    protected $with = ['customer', 'contact'];

    protected $appends = ['total_line_items', 'amount', 'due_amount', 'refundable_amount', 'formated_id', 'has_due', 'has_payment', 'is_paid', 'is_cancelled', 'is_completed', 'can_edit', 'can_refund', 'reference'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->customer();
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
        return $this->morphMany(Payment::class, 'paymentable')->whereIn('status', [Payment::STATUS_COMPLETED, Payment::STATUS_REFUNDED, Payment::STATUS_PARTIALLY_REFUNDED]);
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
        return ! is_null($this->discount) ?: false;
    }

    protected function amount(): Attribute
    {
        return Attribute::make(get: fn () => $this->total());
    }

    protected function dueAmount(): Attribute
    {
        return Attribute::make(get: fn () => round($this->grand_total - $this->paid_total, 2));
    }

    protected function refundableAmount(): Attribute
    {
        return Attribute::make(get: fn () => round($this->paid_total - $this->refund_total, 2));
    }

    protected function formatedId(): Attribute
    {
        return Attribute::make(get: fn () => "#{$this->id}");
    }

    protected function hasDue(): Attribute
    {
        return Attribute::make(get: fn () => $this->due_amount > 0);
    }

    protected function hasPayment(): Attribute
    {
        return Attribute::make(get: fn () => $this->paid_total > 0);
    }

    protected function totalLineItems(): Attribute
    {
        return Attribute::make(get: function () {
            if (! $this->line_items_quantity) {
                return '0 Items';
            }

            return "{$this->line_items_quantity} Item".($this->line_items_quantity > 1 ? 's' : '');
        });
    }

    protected function isCompleted(): Attribute
    {
        return Attribute::make(get: fn () => $this->status === self::STATUS_DELIVERED);
    }

    protected function isCancelled(): Attribute
    {
        return Attribute::make(get: fn () => $this->status === self::STATUS_CANCELLED);
    }

    protected function isPaid(): Attribute
    {
        return Attribute::make(get: fn () => $this->payment_status === self::STATUS_PAID);
    }

    protected function canEdit(): Attribute
    {
        return Attribute::make(get: fn () => ! in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_DELIVERED]));
    }

    protected function canRefund(): Attribute
    {
        return Attribute::make(get: fn () => in_array($this->payment_status, [self::STATUS_PAID]) && ! in_array($this->payment_status, [self::STATUS_REFUNDED]));
    }

    public function setLocationAttribute($location)
    {
        $this->attributes['location_id'] = has($location)->id;
    }

    public function syncLineItems(Collection $line_items, $detach = true)
    {
        if ($detach) {
            $this->line_items()->whereNotIn('id', $line_items->pluck('id')->filter())->each(function ($item) {
                $item->delete();
            });
        }
        foreach ($line_items as $item) {
            $product = $this->line_items()->updateOrCreate(['id' => has($item)->id], Arr::only($item, (new LineItem)->getFillable()));
            if (! empty(has($item)->discount)) {
                $product->discount()->updateOrCreate(['id' => has($item['discount'])->id], (new DiscountLine($item['discount']))->toArray());
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
        $replicate = new Resource($this->replicate(['created_at', 'updated_at', 'due_date'])->toArray());

        return static::modifyOrCreate($replicate);
    }

    public static function modifyOrCreate($resource): self
    {
        if (is_array($resource)) {
            $resource = new Resource($resource);
        }
        $resource->merge(['customer_id' => $resource->input('customer.id') ?? $resource->customer_id]);
        $order = static::updateOrCreate(['id' => has($resource)->id], $resource->only((new static)->getFillable()));
        $order = $order->saveRelated($resource);
        if (! $order->has_due) {
            $order->markAsPaid();
        }

        return $order;
    }

    public function saveRelated($resource): self
    {
        if (is_array($resource)) {
            $resource = new Resource($resource);
        }
        $preserveTaxCalculations = $resource->boolean('preserve_tax_calculations', false);
        if ($preserveTaxCalculations && $resource->filled('tax_lines') && $resource->filled('tax_total')) {
            $this->fill(['sub_total' => $resource->sub_total, 'tax_total' => $resource->tax_total, 'discount_total' => $resource->discount_total ?? 0, 'grand_total' => $resource->grand_total])->save();
            if ($resource->filled('tax_lines')) {
                $tax_lines = collect($resource->tax_lines);
                $this->tax_lines()->whereNotIn('id', $tax_lines->pluck('id')->filter())->delete();
                $tax_lines->each(function ($tax) {
                    $this->tax_lines()->updateOrCreate(['id' => has($tax)->id], $tax);
                });
            }
        } else {
            $cart = new CartRepository($resource->input());
            $resource->merge(['sub_total' => $cart->sub_total, 'tax_lines' => $cart->tax_lines->toArray(), 'tax_total' => $cart->tax_total, 'discount_total' => $cart->discount_total, 'grand_total' => $cart->grand_total, 'line_items_quantity' => $cart->line_items_quantity]);
            $this->fill(['sub_total' => $cart->sub_total, 'tax_total' => $cart->tax_total, 'discount_total' => $cart->discount_total, 'grand_total' => $cart->grand_total, 'line_items_quantity' => $cart->line_items_quantity])->save();
            if ($resource->filled('tax_lines')) {
                $tax_lines = collect($resource->tax_lines);
                $this->tax_lines()->whereNotIn('id', $tax_lines->pluck('id')->filter())->delete();
                $tax_lines->each(function ($tax) {
                    $this->tax_lines()->updateOrCreate(['id' => has($tax)->id], $tax);
                });
            }
        }
        if ($resource->filled('line_items')) {
            $this->syncLineItems(collect($resource->input('line_items')));
        }
        if ($resource->hasAny(['contact.email', 'contact.phone_number'])) {
            if ($resource->boolean('contact.update_customer_profile') && $this->customer) {
                $this->customer->update(Arr::only($resource->contact, ['email', 'phone_number']));
            }
            if ($this->contact) {
                $this->contact->update((new Contact($resource->contact))->toArray());
            } else {
                $this->contact()->save(new Contact($resource->contact));
            }
        }
        if ($resource->filled('discount')) {
            if ($this->discount) {
                $this->discount->update((new DiscountLine($resource->discount))->toArray());
            } else {
                $this->discount()->save(new DiscountLine($resource->discount));
            }
        }
        if ($resource->boolean('discount_removed') ?: false) {
            $this->discount()->delete();
        }

        return $this;
    }

    public function markAsPaid($payment = null, array $transaction = [])
    {
        $order = $this->updatePaymentStatus($payment, $transaction, Payment::STATUS_COMPLETED, self::STATUS_PAID, self::STATUS_PROCESSING);
        $orderable = $order->orderable;
        if ($orderable && method_exists($orderable, 'paymentConfirmation')) {
            $orderable->paymentConfirmation($order);
        }
        event(new \Coderstm\Events\Shop\OrderPaid($order));
        event(new \Coderstm\Events\Shop\PaymentSuccessful($order, $payment));

        return $order;
    }

    public function markAsPaidUsingWallet(array $transaction = [])
    {
        return $this->markAsPaid(\Coderstm\Models\PaymentMethod::walletId(), $transaction);
    }

    public function markAsPaymentPending($payment = null, array $transaction = [])
    {
        $order = $this->updatePaymentStatus($payment, $transaction, Payment::STATUS_PENDING, self::STATUS_PAYMENT_PENDING, self::STATUS_PENDING_PAYMENT);
        $orderable = $order->orderable;
        if ($orderable && method_exists($orderable, 'paymentPending')) {
            $orderable->paymentPending($order);
        }

        return $order;
    }

    public function markAsPaymentFailed($payment = null, array $transaction = [])
    {
        $order = $this->updatePaymentStatus($payment, $transaction, Payment::STATUS_FAILED, self::STATUS_PAYMENT_FAILED, self::STATUS_PENDING_PAYMENT);
        $orderable = $order->orderable;
        if ($orderable && method_exists($orderable, 'paymentFailed')) {
            $orderable->paymentFailed($order);
        }
        $reason = $transaction['note'] ?? $transaction['error'] ?? null;
        event(new \Coderstm\Events\Shop\PaymentFailed($order, $payment, $reason));

        return $order;
    }

    private function updatePaymentStatus($payment, array $transaction, string $paymentStatus, string $orderPaymentStatus, string $orderStatus)
    {
        $this->handlePaymentStatusChange($payment, $transaction, $paymentStatus);
        $this->update(['payment_status' => $orderPaymentStatus, 'status' => $orderStatus]);

        return $this->fresh(['payments']);
    }

    private function handlePaymentStatusChange($payment, array $transaction, string $paymentStatus)
    {
        if ($payment instanceof PaymentInterface) {
            $this->createPayment($payment, $transaction);
        } elseif ($payment) {
            $this->createPayment(['payment_method_id' => $payment, 'transaction_id' => $transaction['id'] ?? null, 'amount' => $transaction['amount'] ?? $this->grand_total, 'status' => $paymentStatus, 'note' => $transaction['note'] ?? null, 'gateway_response' => $transaction['gateway_response'] ?? null, 'processed_at' => $transaction['processed_at'] ?? now(), 'metadata' => $transaction['metadata'] ?? []]);
        }
    }

    protected function reference(): Attribute
    {
        return Attribute::make(get: fn () => 'ORD-'.date('y').$this->id);
    }

    protected function adjustInventory(LineItem $lineItem, $location_id)
    {
        $inventory = Inventory::where(['location_id' => $location_id, 'variant_id' => $lineItem->variant_id])->first();
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
        }
    }

    protected function formatAmount($amount)
    {
        return format_amount($amount);
    }

    protected function billingAddress()
    {
        return (new Address($this->billing_address ?? []))->label;
    }

    public function toPdfArray(): array
    {
        return ['id' => $this->formated_id, 'phone_number' => optional($this->contact)->phone_number, 'customer_name' => optional($this->customer)->name ?? 'NA', 'billing_address' => $this->billingAddress(), 'line_items' => $this->line_items, 'location' => optional($this->location)->address_label, 'sub_total' => $this->formatAmount($this->sub_total), 'tax_total' => $this->formatAmount($this->tax_total), 'discount_total' => $this->formatAmount($this->discount_total), 'grand_total' => $this->formatAmount($this->grand_total), 'paid_total' => $this->formatAmount($this->paid_total), 'due_amount' => $this->formatAmount($this->due_amount), 'created_at' => $this->created_at->format('d-m-Y h:i a'), 'payments' => $this->payments->map(fn ($payment) => $payment->getShortCodes())->toArray()];
    }

    public function posPdf()
    {
        return Pdf::loadView('pdfs.order-pos', $this->toPdfArray())->setPaper([0, 0, 260.0, 600.8]);
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

    public static function findByKey($key): self
    {
        return static::where('key', $key)->firstOrFail();
    }

    protected function label(): string
    {
        return $this->options?->title ?? "#{$this->id}";
    }

    public function toPublic(): array
    {
        return ['key' => $this->key, 'label' => $this->label(), 'line_items' => $this->line_items, 'sub_total' => $this->sub_total, 'tax_total' => $this->tax_total, 'discount_total' => $this->discount_total, 'total' => $this->total(), 'raw_amount' => $this->grand_total, 'status' => $this->status, 'payment_status' => $this->payment_status, 'fulfillment_status' => $this->fulfillment_status, 'amount' => $this->total()];
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
        $updated = $this->update(['status' => self::STATUS_SHIPPED, 'fulfillment_status' => self::STATUS_FULFILLMENT_SHIPPED, 'shipped_at' => now(), 'tracking_number' => $trackingNumber, 'tracking_company' => $trackingCompany]);
        if ($updated) {
            event(new \Coderstm\Events\Shop\OrderShipped($this->fresh()));
        }

        return $updated;
    }

    public function markAsDelivered(): bool
    {
        $updated = $this->update(['status' => self::STATUS_DELIVERED, 'fulfillment_status' => self::STATUS_FULFILLMENT_DELIVERED, 'delivered_at' => now()]);
        if ($updated) {
            event(new \Coderstm\Events\Shop\OrderDelivered($this->fresh()));
        }

        return $updated;
    }

    public function cancel($reason = null): bool
    {
        $updated = $this->update(['status' => self::STATUS_CANCELLED, 'fulfillment_status' => self::STATUS_FULFILLMENT_CANCELLED, 'cancelled_at' => now(), 'cancel_reason' => $reason]);
        if ($updated) {
            event(new \Coderstm\Events\Shop\OrderCanceled($this->fresh()));
        }

        return $updated;
    }

    public function markAsCompleted()
    {
        return $this->markedAsCompleted();
    }

    public function markAsOpen()
    {
        return $this->markedAsOpen();
    }

    protected function shippingAddress()
    {
        return (new Address($this->shipping_address ?? []))->label;
    }

    protected function grossSales(): Attribute
    {
        return Attribute::make(get: fn () => round($this->sub_total + $this->tax_total + $this->shipping_total, 2));
    }

    protected function netSales(): Attribute
    {
        return Attribute::make(get: fn () => round($this->gross_sales - $this->discount_total - $this->refund_total, 2));
    }

    protected function discountRate(): Attribute
    {
        return Attribute::make(get: fn () => $this->gross_sales > 0 ? round($this->discount_total / $this->gross_sales * 100, 2) : 0.0);
    }

    protected function refundRate(): Attribute
    {
        return Attribute::make(get: fn () => $this->gross_sales > 0 ? round($this->refund_total / $this->gross_sales * 100, 2) : 0.0);
    }

    protected function fulfillmentLatency(): Attribute
    {
        return Attribute::make(get: fn () => $this->shipped_at ? round($this->created_at->diffInHours($this->shipped_at), 2) : null);
    }

    protected function deliveryLatency(): Attribute
    {
        return Attribute::make(get: fn () => $this->delivered_at && $this->shipped_at ? round($this->shipped_at->diffInHours($this->delivered_at), 2) : null);
    }

    protected function totalLatency(): Attribute
    {
        return Attribute::make(get: fn () => $this->delivered_at && $this->created_at ? round($this->created_at->diffInHours($this->delivered_at), 2) : null);
    }

    protected function shippingCountry(): Attribute
    {
        return Attribute::make(get: fn () => $this->shipping_address['country_code'] ?? $this->shipping_address['country'] ?? null);
    }

    protected function shippingRegion(): Attribute
    {
        return Attribute::make(get: fn () => $this->shipping_address['state'] ?? $this->shipping_address['region'] ?? null);
    }

    protected function shippingCity(): Attribute
    {
        return Attribute::make(get: fn () => $this->shipping_address['city'] ?? null);
    }

    protected function trackingUrl(): Attribute
    {
        return Attribute::make(get: function () {
            if (! $this->tracking_number || ! $this->tracking_company) {
                return null;
            }
            $carriers = ['ups' => "https://www.ups.com/track?tracknum={$this->tracking_number}", 'fedex' => "https://www.fedex.com/fedextrack/?tracknumbers={$this->tracking_number}", 'usps' => "https://tools.usps.com/go/TrackConfirmAction?tLabels={$this->tracking_number}", 'dhl' => "https://www.dhl.com/en/express/tracking.html?AWB={$this->tracking_number}", 'canada post' => "https://www.canadapost-postescanada.ca/track-reperage/en#/search?searchFor={$this->tracking_number}", 'royal mail' => "https://www.royalmail.com/track-your-item?trackNumber={$this->tracking_number}"];
            $carrier = strtolower($this->tracking_company);

            return $carriers[$carrier] ?? url("/track/{$this->tracking_number}");
        });
    }

    protected function shippingMethod(): Attribute
    {
        return Attribute::make(get: fn () => $this->tracking_company);
    }

    public function isFirstPurchase(): bool
    {
        if (! $this->customer_id) {
            return true;
        }

        return static::where('customer_id', $this->customer_id)->where('payment_status', self::STATUS_PAID)->where('id', '<', $this->id)->doesntExist();
    }

    public function isOverdue(): bool
    {
        return $this->payment_status !== self::STATUS_PAID && $this->due_date && $this->due_date->isPast();
    }

    public function createPayment($attributes = [], array $transaction = [])
    {
        if ($attributes instanceof PaymentInterface) {
            $attributes = $attributes->toArray();
        }
        $attributes = array_merge($attributes, $transaction);

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
            if (empty($model->location)) {
                $model->location = Location::first()?->toArray();
            }
            if (empty($model->location)) {
                $model->location = Location::first()?->toArray();
            }
        });
    }

    public function getShortCodes(): array
    {
        return ['id' => $this->id, 'number' => $this->formated_id, 'name' => "Order {$this->formated_id}", 'date' => optional($this->created_at)->format('d-M-Y'), 'payment_status' => ucfirst($this->payment_status), 'status' => ucfirst($this->status), 'fulfillment_status' => ucfirst(str_replace('_', ' ', $this->fulfillment_status ?? 'pending')), 'sub_total' => $this->formatAmount($this->sub_total ?? 0), 'tax_total' => $this->formatAmount($this->tax_total ?? 0), 'discount_total' => $this->formatAmount($this->discount_total ?? 0), 'total' => $this->formatAmount($this->grand_total ?? 0), 'raw_total' => $this->grand_total ?? 0, 'paid_total' => $this->formatAmount($this->paid_total ?? 0), 'due_amount' => $this->formatAmount($this->due_amount ?? 0), 'tracking_number' => $this->tracking_number, 'tracking_company' => $this->tracking_company, 'tracking_url' => $this->tracking_url, 'shipped_at' => optional($this->shipped_at)->format('M d, Y'), 'delivered_at' => optional($this->delivered_at)->format('M d, Y'), 'estimated_delivery' => $this->shipped_at ? $this->shipped_at->copy()->addDays(5)->format('M d, Y') : null, 'has_due' => (bool) $this->has_due, 'url' => "/orders/{$this->id}", 'payment_url' => app_url("payment/{$this->key}", ['redirect' => user_route('/billing')]), 'refund_total' => $this->formatAmount($this->refund_total ?? 0), 'refundable_amount' => $this->formatAmount($this->refundable_amount ?? 0), 'can_refund' => (bool) $this->can_refund, 'customer' => $this->customer?->getShortCodes() ?? ['id' => null, 'first_name' => 'there', 'last_name' => '', 'name' => 'there', 'email' => '', 'phone' => ''], 'payments' => $this->payments->sortByDesc('created_at')->map(fn ($payment) => $payment->getShortCodes())->values()->toArray(), 'items' => $this->line_items->map(fn ($item) => ['title' => $item->title ?? $item->product?->title ?? 'Product', 'variant_title' => $item->variant_title && $item->variant_title !== 'Default' ? $item->variant_title : null, 'quantity' => $item->quantity, 'price' => $this->formatAmount($item->price ?? 0), 'total' => $this->formatAmount($item->total ?? 0), 'thumbnail' => $item->thumbnail])->toArray()];
    }

    public function getCurrencyFields(): array
    {
        return ['sub_total', 'tax_total', 'discount_total', 'grand_total', 'paid_total', 'refund_total', 'amount', 'due_amount', 'refundable_amount'];
    }

    public function transformForPayment(): array
    {
        $orderData = \Coderstm\Facades\Currency::transform($this);
        if ($this->relationLoaded('line_items')) {
            $orderData['line_items'] = \Coderstm\Facades\Currency::transform($this->line_items);
        }
        if ($this->relationLoaded('tax_lines')) {
            $orderData['tax_lines'] = \Coderstm\Facades\Currency::transform($this->tax_lines);
        }
        if ($this->relationLoaded('discount') && $this->discount) {
            $orderData['discount'] = \Coderstm\Facades\Currency::transform($this->discount);
        }

        return $orderData;
    }
}
