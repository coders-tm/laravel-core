<?php

namespace Coderstm\Actions\Shop;

use Coderstm\Coderstm;
use Coderstm\Models\Shop\Order\Contact;
use Coderstm\Models\Shop\Order\DiscountLine;
use Coderstm\Repository\CartRepository;
use Coderstm\Services\Resource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateOrCreate
{
    public function __call($method, $parameters)
    {
        return $this->{$method}(...$parameters);
    }

    public function __invoke($resource, $options = [], $order = null)
    {
        return $this->execute($resource, $options, $order);
    }

    public function execute($resource, $options = [], $order = null)
    {
        if (is_array($resource)) {
            $resource = new Resource($resource);
        }

        return DB::transaction(function () use ($resource, $order, $options) {
            $resource->merge(['customer_id' => $resource->input('customer.id') ?? $resource->customer_id]);
            $fillableData = $resource->only((new Coderstm::$orderModel)->getFillable());
            if ($order && $order->exists) {
                $order->fill($fillableData);
            } else {
                $order = new Coderstm::$orderModel($fillableData);
            }
            $this->save($order, $resource, $options);
            if (! $order->has_due && ! $order->is_cancelled) {
                $order->markAsPaid();
            }

            return $order->fresh();
        });
    }

    public function save($order, $resource, array $options = []): void
    {
        if (is_array($resource)) {
            $resource = new Resource($resource);
        }
        $preserveTaxCalculations = $resource->boolean('preserve_tax_calculations', false);
        if ($preserveTaxCalculations && $resource->filled('tax_lines') && $resource->filled('tax_total')) {
            $order->fill(['sub_total' => $resource->sub_total, 'tax_total' => $resource->tax_total, 'discount_total' => $resource->discount_total ?? 0, 'grand_total' => $resource->grand_total])->save();
            if ($resource->filled('tax_lines')) {
                $tax_lines = collect($resource->tax_lines);
                $order->tax_lines()->whereNotIn('id', $tax_lines->pluck('id')->filter())->delete();
                $tax_lines->each(function ($tax) use ($order) {
                    $order->tax_lines()->updateOrCreate(['id' => has($tax)->id], $tax);
                });
            }
        } else {
            $cart = new CartRepository($resource->input());
            $order->fill(['sub_total' => $cart->sub_total, 'tax_total' => $cart->tax_total, 'discount_total' => $cart->discount_total, 'grand_total' => $cart->grand_total, 'line_items_quantity' => $cart->line_items_quantity])->save();
            if ($resource->filled('tax_lines')) {
                $tax_lines = collect($resource->tax_lines);
                $order->tax_lines()->whereNotIn('id', $tax_lines->pluck('id')->filter())->delete();
                $tax_lines->each(function ($tax) use ($order) {
                    $order->tax_lines()->updateOrCreate(['id' => has($tax)->id], $tax);
                });
            }
        }
        if ($resource->filled('line_items')) {
            $order->syncLineItems(collect($resource->input('line_items')));
        }
        if ($resource->hasAny(['contact.email', 'contact.phone_number'])) {
            if ($resource->boolean('contact.update_customer_profile') && $order->customer) {
                $order->customer->update(Arr::only($resource->contact, ['email', 'phone_number']));
            }
            if ($order->contact) {
                $order->contact->update((new Contact($resource->contact))->toArray());
            } else {
                $order->contact()->save(new Contact($resource->contact));
            }
        }
        if ($resource->filled('discount')) {
            if ($order->discount) {
                $order->discount->update((new DiscountLine($resource->discount))->toArray());
            } else {
                $order->discount()->save(new DiscountLine($resource->discount));
            }
        }
        if ($resource->boolean('discount_removed') ?: false) {
            $order->discount()->delete();
        }
    }
}
