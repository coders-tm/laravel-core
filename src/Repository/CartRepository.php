<?php

namespace Coderstm\Repository;

use Coderstm\Models\Address;
use Coderstm\Models\Shop\Customer;
use Coderstm\Models\Shop\Order;
use Coderstm\Models\Shop\Order\Contact;
use Coderstm\Models\Shop\Order\DiscountLine;
use Coderstm\Models\Shop\Order\LineItem;
use Illuminate\Http\Request;

class CartRepository extends BaseRepository
{
    public static function fromRequest(Request $request, Order $order): Order
    {
        $line_items = collect($request->line_items ?? [])->map(function ($product) {
            return LineItem::firstOrNew(['id' => $product['id'] ?? null], $product)->fill($product);
        });
        $order->created_at = $order->created_at ?? now();
        $order->fill($request->only(['note', 'collect_tax', 'attributes', 'billing_address', 'shipping_address', 'source']));
        if (! $request->filled('collect_tax')) {
            $order->collect_tax = true;
        }
        $order->setRelation('line_items', $line_items);
        if ($request->filled('line_items')) {
            foreach ($request->line_items as $key => $product) {
                if (isset($product['discount'])) {
                    $order->line_items[$key]->setRelation('discount', DiscountLine::firstOrNew(['id' => $product['discount']['id'] ?? null], $product['discount'])->fill($product['discount']));
                }
            }
        }
        $order->setRelation('discount', $request->filled('discount') ? new DiscountLine($request->discount) : null);
        if ($request->filled('customer')) {
            $customer = new Customer($request->customer);
            if ($request->filled('customer.address')) {
                $customer->setRelation('address', new Address($request->input('customer.address')));
            }
            $order->setRelation('customer', $customer);
            $order->customer->created_at = $order->customer->created_at ?? now();
            if ($request->filled('customer.id')) {
                $order->customer->id = $request->input('customer.id');
            }
        } else {
            $order->setRelation('customer', null);
        }
        $order->setRelation('contact', $request->filled('contact') ? new Contact($request->contact) : null);
        $cartRepository = new static($order->toArray());
        $order->setRelation('tax_lines', $cartRepository->tax_lines);
        $order->fill(['sub_total' => $cartRepository->sub_total, 'tax_total' => $cartRepository->tax_total, 'discount_total' => $cartRepository->discount_total, 'grand_total' => $cartRepository->grand_total, 'line_items_quantity' => $cartRepository->line_items_quantity]);

        return $order;
    }
}
