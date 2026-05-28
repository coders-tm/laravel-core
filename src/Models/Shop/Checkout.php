<?php

namespace Coderstm\Models\Shop;

use Coderstm\Coderstm;
use Coderstm\Database\Factories\Shop\CheckoutFactory;
use Coderstm\Facades\Shop;
use Coderstm\Models\Shop\Order\DiscountLine;
use Coderstm\Models\Shop\Order\LineItem;
use Coderstm\Models\Shop\Order\TaxLine;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Checkout extends Model
{
    use HasFactory, Notifiable, SerializeDate;

    protected $fillable = ['token', 'type', 'user_id', 'cart_token', 'order_id', 'email', 'first_name', 'last_name', 'phone_number', 'shipping_address', 'billing_address', 'same_as_billing', 'coupon_code', 'sub_total', 'tax_total', 'shipping_total', 'discount_total', 'grand_total', 'transaction_id', 'note', 'internal_note', 'status', 'email_status', 'recovery_status', 'metadata', 'started_at', 'completed_at', 'abandoned_at', 'recovery_email_sent_at'];

    protected $casts = ['shipping_address' => 'json', 'billing_address' => 'json', 'metadata' => 'json', 'same_as_billing' => 'boolean', 'sub_total' => 'decimal:2', 'tax_total' => 'decimal:2', 'shipping_total' => 'decimal:2', 'discount_total' => 'decimal:2', 'grand_total' => 'decimal:2', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'abandoned_at' => 'datetime', 'recovery_email_sent_at' => 'datetime'];

    protected $appends = ['contact', 'is_completed', 'total_line_items', 'reference'];

    protected $with = ['line_items', 'discount', 'tax_lines'];

    const STATUS_DRAFT = 'draft';

    const STATUS_PENDING = 'pending';

    const STATUS_COMPLETED = 'completed';

    const STATUS_ABANDONED = 'abandoned';

    const STATUS_EMAIL_SENT = 'email_sent';

    const STATUS_EMAIL_NOT_SENT = 'email_not_sent';

    const STATUS_EMAIL_SCHEDULED = 'email_scheduled';

    const STATUS_NOT_RECOVERED = 'not_recovered';

    const STATUS_RECOVERED = 'recovered';

    const TYPE_STANDARD = 'standard';

    const TYPE_SUBSCRIPTION = 'subscription';

    public function user()
    {
        return $this->belongsTo(Coderstm::$userModel);
    }

    public function customer()
    {
        return $this->user();
    }

    public function order()
    {
        return $this->belongsTo(Coderstm::$orderModel, 'order_id');
    }

    public function line_items()
    {
        return $this->morphMany(LineItem::class, 'itemable');
    }

    public function discount()
    {
        return $this->morphOne(DiscountLine::class, 'discountable');
    }

    public function tax_lines()
    {
        return $this->morphMany(TaxLine::class, 'taxable');
    }

    private static function newCheckout(string $type = self::TYPE_STANDARD, array $data = []): self
    {
        $checkout = new self;
        $checkout->type = $type;
        $checkout->status = 'draft';
        $checkout->status = self::STATUS_DRAFT;
        $checkout->fill($data);
        $checkout->save();
        $lineItems = $data['line_items'] ?? [];
        if (! empty($lineItems)) {
            $checkout->syncLineItems(collect($lineItems));
        }

        return $checkout;
    }

    public static function createStandardCheckout(array $data = []): self
    {
        return self::newCheckout(self::TYPE_STANDARD, $data);
    }

    public static function createSubscriptionCheckout(array $data = []): self
    {
        return self::newCheckout(self::TYPE_SUBSCRIPTION, $data);
    }

    public static function getOrCreate(array $data = []): self
    {
        $cartToken = Shop::token();
        $userId = Auth::guard('sanctum')->check() ? Auth::guard('sanctum')->id() : null;
        $user = Auth::guard('sanctum')->user();
        $isSubscription = isset($data['type']) && $data['type'] === 'subscription';
        if ($cartToken) {
            $checkout = self::where('cart_token', $cartToken)->where('type', $isSubscription ? 'subscription' : 'standard')->whereIn('status', ['draft', 'pending'])->first();
            if ($checkout) {
                if ($userId && ! $checkout->user_id) {
                    $checkout->update(['user_id' => $userId, 'email' => $user?->email ?? $checkout->email, 'first_name' => $user?->first_name ?? $checkout->first_name, 'last_name' => $user?->last_name ?? $checkout->last_name, 'phone_number' => $user?->phone_number ?? $checkout->phone_number, 'billing_address' => $user?->address?->toArray() ?? $checkout->billing_address]);
                }

                return $checkout;
            }
        }
        if ($userId) {
            $checkout = self::where('user_id', $userId)->where('type', $isSubscription ? 'subscription' : 'standard')->whereIn('status', ['draft', 'pending'])->latest()->first();
            if ($checkout) {
                return $checkout;
            }
        }
        $data = array_merge($data, ['cart_token' => $cartToken, 'user_id' => $userId, 'email' => $user?->email ?? null, 'first_name' => $user?->first_name ?? null, 'last_name' => $user?->last_name ?? null, 'phone_number' => $user?->phone_number ?? null, 'billing_address' => $user?->address?->toArray() ?? null]);
        if ($isSubscription) {
            $checkout = self::createSubscriptionCheckout($data);
        } else {
            $checkout = self::createStandardCheckout($data);
        }
        $checkout->save();

        return $checkout;
    }

    public function markAsStarted()
    {
        $this->update(['status' => 'pending', 'started_at' => now()]);
    }

    public function markAsCompleted($transactionId = null, $paymentData = [])
    {
        $this->update(['status' => 'completed', 'payment_status' => 'paid', 'completed_at' => now(), 'transaction_id' => $transactionId, 'payment_data' => $paymentData]);
    }

    public function markAsAbandoned()
    {
        $this->update(['status' => 'abandoned', 'payment_status' => 'abandoned', 'abandoned_at' => now()]);
    }

    public function isAbandoned(): bool
    {
        return $this->status === 'abandoned' || $this->started_at && $this->started_at->diffInHours(now()) >= 12 && ! $this->completed_at;
    }

    public function canSendRecoveryEmail(): bool
    {
        return $this->email && $this->isAbandoned() && (! $this->recovery_email_sent_at || $this->recovery_email_sent_at->diffInHours(now()) >= 24);
    }

    public function getCheckoutUrl(): string
    {
        return url("/shop/checkout/{$this->token}");
    }

    public function getFullName(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
    }

    public function isSubscription(): bool
    {
        return $this->type === 'subscription';
    }

    public function isStandard(): bool
    {
        return $this->type === 'standard' || $this->type === null;
    }

    public function hasDiscount(): bool
    {
        return ! is_null($this->discount);
    }

    public function hasLineItems(): bool
    {
        return $this->line_items()->exists();
    }

    public function getLineItemsCount(): int
    {
        return $this->line_items()->sum('quantity');
    }

    public function syncLineItems($line_items, $detach = true)
    {
        if (! $line_items instanceof Collection) {
            $line_items = collect($line_items);
        }
        if ($detach) {
            $this->line_items()->whereNotIn('id', $line_items->pluck('id')->filter())->each(function ($product) {
                $product->delete();
            });
        }
        foreach ($line_items as $item) {
            $itemData = is_array($item) ? $item : $item->toArray();
            $product = $this->line_items()->updateOrCreate(['id' => has($itemData)->id], Arr::only($itemData, (new LineItem)->getFillable()));
            if (! empty($itemData['discount'])) {
                $product->discount()->updateOrCreate(['id' => $itemData['discount']['id'] ?? null], $itemData['discount']);
            } else {
                $product->discount()->delete();
            }
        }
    }

    public function syncLineItemsWithoutDetach($line_items)
    {
        if (! $line_items instanceof Collection) {
            $line_items = collect($line_items);
        }
        $this->syncLineItems($line_items, false);
    }

    public function syncTaxLines($tax_lines, $detach = true)
    {
        if (! $tax_lines instanceof Collection) {
            $tax_lines = collect($tax_lines);
        }
        if ($detach) {
            $this->tax_lines()->whereNotIn('id', $tax_lines->pluck('id')->filter())->each(function ($taxLine) {
                $taxLine->delete();
            });
        }
        foreach ($tax_lines as $item) {
            $itemData = is_array($item) ? $item : $item->toArray();
            $this->tax_lines()->updateOrCreate(['id' => has($itemData)->id], Arr::only($itemData, (new TaxLine)->getFillable()));
        }
    }

    public function syncTaxLinesWithoutDetach($tax_lines)
    {
        $this->syncTaxLines($tax_lines, false);
    }

    public function scopeAbandoned($query)
    {
        return $query->where('status', 'abandoned')->orWhere(function ($q) {
            $q->whereIn('status', ['draft', 'pending'])->where('started_at', '<', now()->subHours(12));
        });
    }

    public function scopeRecoveryEligible($query)
    {
        return $query->abandoned()->whereNotNull('email')->where(function ($q) {
            $q->whereNull('recovery_email_sent_at')->orWhere('recovery_email_sent_at', '<', now()->subHours(24));
        });
    }

    public function scopeSortBy($query, $column = 'CREATED_AT_ASC', $direction = 'asc')
    {
        switch ($column) {
            case 'CUSTOMER_NAME_ASC':
                $query->orderByRaw('CONCAT(`first_name`, `first_name`) ASC');
                break;
            case 'CUSTOMER_NAME_DESC':
                $query->orderByRaw('CONCAT(`first_name`, `first_name`) DESC');
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

    protected function contact(): Attribute
    {
        return Attribute::make(get: fn () => ['email' => $this->email, 'first_name' => $this->first_name, 'last_name' => $this->last_name, 'phone_number' => $this->phone_number]);
    }

    protected function reference(): Attribute
    {
        return Attribute::make(get: fn () => 'CHO'.date('y').$this->id);
    }

    protected function isCompleted(): Attribute
    {
        return Attribute::make(get: fn () => $this->status === self::STATUS_COMPLETED);
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

    public static function getCartToken(Request $request): ?string
    {
        return Shop::token($request);
    }

    protected static function uuid(string $key = 'token'): string
    {
        $token = Str::uuid();
        while (static::where($key, $token)->first()) {
            $token = Str::uuid();
        }

        return $token;
    }

    protected static function newFactory()
    {
        return CheckoutFactory::new();
    }

    protected static function booted()
    {
        parent::booted();
        static::creating(function ($checkout) {
            if (empty($checkout->token)) {
                $checkout->token = static::uuid();
            }
        });
        static::addGlobalScope('count', function (Builder $builder) {
            $builder->withSum('line_items as line_items_quantity', 'quantity');
        });
    }

    public function getShortCodes(): array
    {
        return ['id' => $this->id, 'token' => $this->token, 'email' => $this->email, 'customer_name' => $this->getFullName(), 'first_name' => $this->first_name, 'last_name' => $this->last_name, 'phone_number' => $this->phone_number, 'sub_total' => format_amount($this->sub_total ?? 0), 'tax_total' => format_amount($this->tax_total ?? 0), 'shipping_total' => format_amount($this->shipping_total ?? 0), 'discount_total' => format_amount($this->discount_total ?? 0), 'grand_total' => format_amount($this->grand_total ?? 0), 'raw_total' => $this->grand_total ?? 0, 'status' => ucfirst($this->status ?? 'draft'), 'is_subscription' => $this->isSubscription(), 'started_at' => optional($this->started_at)->format('M d, Y h:i A'), 'abandoned_at' => optional($this->abandoned_at)->format('M d, Y h:i A'), 'completed_at' => optional($this->completed_at)->format('M d, Y h:i A'), 'checkout_url' => $this->getCheckoutUrl(), 'recovery_url' => app_url('cart', ['token' => $this->token, 'recover' => 1]), 'is_abandoned' => $this->isAbandoned(), 'is_completed' => $this->is_completed, 'has_discount' => $this->hasDiscount(), 'item_count' => $this->getLineItemsCount(), 'items' => $this->line_items->map(fn ($item) => ['name' => $item->description, 'quantity' => $item->quantity, 'price' => format_amount($item->price ?? 0), 'total' => format_amount($item->total ?? 0)])->toArray(), 'customer' => $this->customer ? ['name' => $this->customer->name, 'email' => $this->customer->email, 'phone_number' => $this->customer->phone_number] : null, 'shipping_address' => $this->shipping_address, 'billing_address' => $this->billing_address, 'coupon_code' => $this->coupon_code, 'discount' => $this->discount ? ['code' => $this->discount->coupon_code, 'amount' => format_amount($this->discount->amount ?? 0), 'type' => $this->discount->discount_type] : null];
    }

    public function routeNotificationForMail($notification)
    {
        return $this->email;
    }
}
