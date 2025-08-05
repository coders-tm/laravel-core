<?php

namespace Coderstm\Models\Shop;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Coderstm\Models\Shop\Order;
use Coderstm\Traits\SerializeDate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Coderstm\Models\Shop\Order\TaxLine;
use Illuminate\Database\Eloquent\Model;
use Coderstm\Models\Shop\Order\LineItem;
use Illuminate\Database\Eloquent\Builder;
use Coderstm\Models\Shop\Order\DiscountLine;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Coderstm\Database\Factories\Shop\CheckoutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Checkout extends Model
{
    use HasFactory, SerializeDate;

    protected $fillable = [
        'token',
        'type',
        'user_id',
        'session_id',
        'order_id',
        'email',
        'first_name',
        'last_name',
        'phone_number',
        'shipping_address',
        'billing_address',
        'same_as_billing',
        'coupon_code',
        'sub_total',
        'tax_total',
        'shipping_total',
        'discount_total',
        'grand_total',
        'currency',
        'transaction_id',
        'note',
        'internal_note',
        'status',
        'email_status',
        'recovery_status',
        'metadata',
        'started_at',
        'completed_at',
        'abandoned_at',
        'recovery_email_sent_at',
    ];

    protected $casts = [
        'shipping_address' => 'json',
        'billing_address' => 'json',
        'metadata' => 'json',
        'same_as_billing' => 'boolean',
        'sub_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'abandoned_at' => 'datetime',
        'recovery_email_sent_at' => 'datetime',
    ];

    protected $appends = ['contact', 'is_completed', 'total_line_items'];

    protected $with = [
        'line_items',
        'discount',
        'tax_lines',
    ];

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
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->user();
    }

    public function order()
    {
        return $this->hasOne(Order::class, 'checkout_token', 'token');
    }

    /**
     * Get all of the checkout's line_items.
     */
    public function line_items()
    {
        return $this->morphMany(LineItem::class, 'itemable');
    }

    /**
     * Get the checkout's discount.
     */
    public function discount()
    {
        return $this->morphOne(DiscountLine::class, 'discountable');
    }

    /**
     * Get all of the checkout's tax lines.
     */
    public function tax_lines()
    {
        return $this->morphMany(TaxLine::class, 'taxable');
    }

    private static function newCheckout(string $type = self::TYPE_STANDARD, array $data = []): self
    {
        $checkout = new self();

        // Set default values for standard checkout
        $checkout->type = $type;
        $checkout->currency = config('app.currency', 'USD');
        $checkout->status = 'draft';

        $checkout->status = self::STATUS_DRAFT;

        $checkout->fill($data);

        // Save the checkout first to get an ID
        $checkout->save();

        $lineItems = $data['line_items'] ?? [];

        // Set line items through relationship if provided
        if (!empty($lineItems)) {
            $checkout->syncLineItems(collect($lineItems));
        }

        return $checkout;
    }

    public static function createStandardCheckout(Request $request, array $data = []): self
    {
        $checkout = self::newCheckout(self::TYPE_STANDARD, $data);

        // Set session
        $checkout->session_id = $request->session()->getId();

        return $checkout;
    }

    public static function createSubscriptionCheckout(Request $request, array $data = []): self
    {
        $checkout = self::newCheckout(self::TYPE_SUBSCRIPTION, $data);

        // Set session and user info
        $checkout->session_id = $request->session()->getId();

        return $checkout;
    }

    public static function getOrCreate(Request $request, array $data = []): self
    {
        // Try to find existing checkout by token
        if ($request->has('checkout_token')) {
            $checkout = self::where('token', $request->checkout_token)->first();
            if ($checkout) {
                return $checkout;
            }
        }

        $sessionId = $request->session()->getId();
        $userId = Auth::guard('sanctum')->check() ? Auth::guard('sanctum')->id() : null;
        $user = Auth::guard('sanctum')->user();
        $isSubscription = isset($data['type']) && $data['type'] === 'subscription';

        // For subscription checkouts, use different search criteria
        if ($isSubscription) {
            // For subscriptions, try to find by type and session/user
            $checkout = self::where('session_id', $sessionId)
                ->where('type', 'subscription')
                ->whereIn('status', ['draft', 'pending'])
                ->latest()
                ->first();

            if (!$checkout && $userId) {
                $checkout = self::where('user_id', $userId)
                    ->where('type', 'subscription')
                    ->whereIn('status', ['draft', 'pending'])
                    ->latest()
                    ->first();
            }
        } else {
            // For regular checkouts, use standard type
            $checkout = self::where('session_id', $sessionId)
                ->where('type', 'standard')
                ->whereIn('status', ['draft', 'pending'])
                ->latest()
                ->first();

            // If not found, try by user_id
            if (!$checkout && $userId) {
                $checkout = self::where('user_id', $userId)
                    ->where('type', 'standard')
                    ->whereIn('status', ['draft', 'pending'])
                    ->latest()
                    ->first();
            }
        }

        // 3. If still not found, create new
        if (!$checkout) {
            // Check if the user is authenticated
            if ($userId) {
                $data = array_merge($data, [
                    'user_id' => $userId,
                    'email' => $user->email ?? null,
                    'first_name' => $user->first_name ?? null,
                    'last_name' => $user->last_name ?? null,
                    'phone_number' => $user->phone_number ?? null,
                    'billing_address' => $user->address?->toArray() ?? null,
                ]);
            }

            if ($isSubscription) {
                // Create subscription checkout without cart
                $checkout = self::createSubscriptionCheckout($request, $data);
            } else {
                // Create standard checkout
                $checkout = self::createStandardCheckout($request, $data);
            }
            $checkout->save();
        } else {
            // Update existing checkout with new data
            $checkout->fill($data);
            $checkout->save();
        }

        return $checkout;
    }

    public function markAsStarted()
    {
        $this->update([
            'status' => 'pending',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted($transactionId = null, $paymentData = [])
    {
        $this->update([
            'status' => 'completed',
            'payment_status' => 'paid',
            'completed_at' => now(),
            'transaction_id' => $transactionId,
            'payment_data' => $paymentData,
        ]);
    }

    public function markAsAbandoned()
    {
        $this->update([
            'status' => 'abandoned',
            'payment_status' => 'abandoned',
            'abandoned_at' => now(),
        ]);
    }

    public function isAbandoned(): bool
    {
        return $this->status === 'abandoned' ||
            ($this->started_at && $this->started_at->diffInHours(now()) >= 12 && !$this->completed_at);
    }

    public function canSendRecoveryEmail(): bool
    {
        return $this->email &&
            $this->isAbandoned() &&
            (!$this->recovery_email_sent_at || $this->recovery_email_sent_at->diffInHours(now()) >= 24);
    }

    public function getCheckoutUrl(): string
    {
        return url("/shop/checkout/{$this->token}");
    }

    public function getFullName(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
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
        return !is_null($this->discount);
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
        if (!$line_items instanceof Collection) {
            $line_items = collect($line_items);
        }

        if ($detach) {
            // delete removed line_items
            $this->line_items()->whereNotIn('id', $line_items->pluck('id')->filter())->each(function ($product) {
                $product->delete();
            });
        }

        // update or create line_items
        foreach ($line_items as $item) {
            // Convert LineItem object to array if needed
            $itemData = is_array($item) ? $item : $item->toArray();

            // update or create the product
            $product = $this->line_items()->updateOrCreate([
                'id' => has($itemData)->id,
            ], Arr::only($itemData, (new LineItem())->getFillable()));

            // update the discount
            if (!empty($itemData['discount'])) {
                $product->discount()->updateOrCreate([
                    'id' => $itemData['discount']['id'] ?? null,
                ], $itemData['discount']);
            } else {
                $product->discount()->delete();
            }
        }
    }

    public function syncLineItemsWithoutDetach($line_items)
    {
        if (!$line_items instanceof Collection) {
            $line_items = collect($line_items);
        }

        $this->syncLineItems($line_items, false);
    }

    public function syncTaxLines($tax_lines, $detach = true)
    {
        if (!$tax_lines instanceof Collection) {
            $tax_lines = collect($tax_lines);
        }

        if ($detach) {
            // delete removed tax_lines
            $this->tax_lines()->whereNotIn('id', $tax_lines->pluck('id')->filter())->each(function ($taxLine) {
                $taxLine->delete();
            });
        }

        // update or create tax_lines
        foreach ($tax_lines as $item) {
            // Convert TaxLine object to array if needed
            $itemData = is_array($item) ? $item : $item->toArray();

            // update or create the tax line
            $this->tax_lines()->updateOrCreate([
                'id' => has($itemData)->id,
            ], Arr::only($itemData, (new TaxLine())->getFillable()));
        }
    }

    public function syncTaxLinesWithoutDetach($tax_lines)
    {
        $this->syncTaxLines($tax_lines, false);
    }

    // Scope for abandoned checkouts
    public function scopeAbandoned($query)
    {
        return $query->where('status', 'abandoned')
            ->orWhere(function ($q) {
                $q->whereIn('status', ['draft', 'pending'])
                    ->where('started_at', '<', now()->subHours(12));
            });
    }

    // Scope for recovery eligible checkouts
    public function scopeRecoveryEligible($query)
    {
        return $query->abandoned()
            ->whereNotNull('email')
            ->where(function ($q) {
                $q->whereNull('recovery_email_sent_at')
                    ->orWhere('recovery_email_sent_at', '<', now()->subHours(24));
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
        return Attribute::make(
            get: fn() => [
                'email' => $this->email,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'phone_number' => $this->phone_number,
            ],
        );
    }

    protected function isCompleted(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === self::STATUS_COMPLETED,
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

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return CheckoutFactory::new();
    }

    protected static function booted()
    {
        parent::booted();

        static::creating(function ($checkout) {
            if (empty($checkout->token)) {
                $checkout->token = Str::uuid();
            }
        });

        static::addGlobalScope('count', function (Builder $builder) {
            $builder->withSum('line_items as line_items_quantity', 'quantity');
        });
    }
}
