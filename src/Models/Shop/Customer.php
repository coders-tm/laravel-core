<?php

namespace Coderstm\Models\Shop;

use Coderstm\Models\User;
use Coderstm\Traits\Orderable;
use Illuminate\Database\Eloquent\Builder;

class Customer extends User
{
    use Orderable;

    protected $table = 'users';

    protected $fillable = ['first_name', 'last_name', 'email', 'phone_number', 'dob', 'gender', 'note', 'email_marketing', 'collect_tax', 'is_active'];

    protected $casts = ['email_marketing' => 'boolean', 'is_active' => 'boolean', 'collect_tax' => 'boolean'];

    protected $appends = ['name', 'average_order_amount'];

    protected $with = ['address', 'latestOrder'];

    public function getMorphClass()
    {
        return 'User';
    }

    public function getAverageOrderAmountAttribute()
    {
        if ($this->total_orders > 0) {
            return round($this->total_spent / $this->total_orders);
        } else {
            return 0;
        }
    }

    public function getNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    protected static function booted()
    {
        parent::booted();
        static::addGlobalScope('default', function (Builder $builder) {
            $builder->withSum('orders as total_spent', 'grand_total')->withCount('orders as total_orders');
        });
    }

    public function getShortCodes(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'first_name' => $this->first_name, 'last_name' => $this->last_name, 'email' => $this->email, 'phone_number' => $this->phone_number, 'email_marketing' => (bool) $this->email_marketing, 'collect_tax' => (bool) $this->collect_tax, 'gender' => $this->gender, 'dob' => optional($this->dob)->format('M d, Y'), 'total_orders' => $this->total_orders ?? 0, 'total_spent' => format_amount($this->total_spent ?? 0), 'average_order_amount' => format_amount($this->average_order_amount ?? 0), 'address' => $this->address ? ['line1' => $this->address->line1, 'line2' => $this->address->line2, 'city' => $this->address->city, 'state' => $this->address->state, 'country' => $this->address->country, 'zip_code' => $this->address->zip_code, 'full' => $this->address->full_address] : null, 'latest_order' => $this->latestOrder ? ['id' => $this->latestOrder->id, 'number' => $this->latestOrder->formated_id, 'total' => format_amount($this->latestOrder->grand_total ?? 0), 'date' => optional($this->latestOrder->created_at)->format('M d, Y')] : null, 'is_active' => (bool) $this->is_active, 'member_since' => optional($this->created_at)->format('M Y'), 'note' => $this->note];
    }
}
