<?php

namespace Coderstm\Services;

use Coderstm\Data\CartData;
use Coderstm\Models\Shop\Checkout;
use Coderstm\Models\Shop\Product;
use Coderstm\Repository\CheckoutRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopService
{
    public function checkout(): ?Checkout
    {
        try {
            $cartToken = $this->token();
            if ($cartToken) {
                $checkout = Checkout::where('cart_token', $cartToken)->whereIn('status', ['draft', 'pending'])->first();
                if ($checkout) {
                    return $checkout;
                }
            }
            $userId = null;
            if (function_exists('auth')) {
                try {
                    $userId = auth('sanctum')->check() ? auth('sanctum')->id() : null;
                } catch (\Throwable $e) {
                }
            }
            if ($userId) {
                $checkout = Checkout::where('user_id', $userId)->whereIn('status', ['draft', 'pending'])->latest()->first();
                if ($checkout) {
                    return $checkout;
                }
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('ShopService::checkout Error: '.$e->getMessage());

            return new Checkout;
        }
    }

    public function cart(): CartData
    {
        $checkout = $this->checkout();
        if (! $checkout) {
            return new CartData(['count' => 0, 'uniqueItemCount' => 0, 'items' => [], 'subtotal' => 0.0, 'formattedSubtotal' => format_amount(0), 'isEmpty' => true]);
        }
        $checkoutRepository = CheckoutRepository::fromCheckout($checkout);

        return new CartData(['count' => $checkout->line_items->sum('quantity'), 'uniqueItemCount' => $checkout->line_items->count(), 'items' => $checkoutRepository->getCartItems(), 'subtotal' => (float) ($checkout->sub_total ?? 0), 'formattedSubtotal' => format_amount($checkout->sub_total ?? 0), 'isEmpty' => $checkout->line_items->isEmpty()]);
    }

    public function token(?Request $request = null): ?string
    {
        $request = $request ?: request();
        $token = $request->header('X-Cart-Token') ?? $request->cookie('cart_token');

        return $token;
    }

    public function addToCart(array $data): Checkout
    {
        $cart = $this->checkout() ?? Checkout::getOrCreate();
        $product = Product::findOrFail($data['product']);
        $variant = null;
        if (isset($data['variant'])) {
            if ($product->default_variant && $product->default_variant->id == $data['variant']) {
                $variant = $product->default_variant;
            } else {
                $variant = $product->variants()->where('id', $data['variant'])->first();
            }
        } else {
            $variant = $product->default_variant;
        }
        if (! $variant) {
            if ($product->variants()->count() > 0) {
                throw new \Exception('Please select a variant');
            }
            throw new \Exception('Product unavailable');
        }
        $price = $variant->price;
        $quantity = $data['quantity'] ?? 1;
        $existing = $cart->line_items()->where('product_id', $product->id)->where('variant_id', $variant->id)->first();
        if ($existing) {
            $existing->increment('quantity', $quantity);
        } else {
            $cart->line_items()->create(['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => $quantity, 'title' => $product->title, 'slug' => $product->slug, 'price' => $variant->price, 'taxable' => true, 'has_variant' => $product->has_variant, 'variant_title' => $variant->title, 'sku' => $variant->sku, 'options' => $variant->getOptions()]);
        }
        $cart->load('line_items');

        return $this->refreshCartTotals($cart);
    }

    public function updateCartItem(int|string $lineItemId, int $quantity): Checkout
    {
        $cart = $this->checkout();
        if (! $cart) {
            throw new \Exception('Cart not found');
        }
        $lineItem = $cart->line_items()->where('id', $lineItemId)->first();
        if (! $lineItem) {
            throw new \Exception('Item not found in cart');
        }
        if ($quantity > 0) {
            $lineItem->update(['quantity' => $quantity]);
        } else {
            $lineItem->forceDelete();
        }
        $cart->load('line_items');

        return $this->refreshCartTotals($cart);
    }

    public function removeCartItem(int|string $lineItemId): Checkout
    {
        $cart = $this->checkout();
        if (! $cart) {
            throw new \Exception('Cart not found');
        }
        $lineItem = $cart->line_items()->where('id', $lineItemId)->first();
        if ($lineItem) {
            $lineItem->forceDelete();
        }
        $cart->load('line_items');

        return $this->refreshCartTotals($cart);
    }

    public function clearCart(): Checkout
    {
        $cart = $this->checkout();
        if (! $cart) {
            throw new \Exception('Cart not found');
        }
        $cart->line_items()->forceDelete();
        $cart->discount()->forceDelete();
        $cart->tax_lines()->forceDelete();
        $cart->update(['coupon_code' => null]);
        $cart->load(['line_items', 'discount', 'tax_lines']);

        return $this->refreshCartTotals($cart);
    }

    protected function refreshCartTotals(Checkout $cart): Checkout
    {
        $repository = CheckoutRepository::fromCheckout($cart);
        $repository->calculate();

        return $repository->checkout;
    }
}
