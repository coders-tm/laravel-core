<div>
    <p>Hi <b>{{ $cart->first_name }}</b>,</p>
    <p>We noticed you left some items in your cart! Don't worry, we've saved them for you.</p>

    <h3 class="section-title" style="margin-top:0">Your Cart Summary</h3>

    <table class="card-table order-items" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <thead>
            <tr>
                <th align="left">Item</th>
                <th align="center" width="60">Qty</th>
                <th align="right" width="80">Price</th>
                <th align="right" width="100">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cart->items as $item)
            <tr>
                <td class="item-row">
                    <div class="item-wrap">
                        <img src="{{ $item->thumbnail ?? 'https://placehold.co/80x80?text=No+Image' }}" alt="{{ $item->title ?? $item->description }}" class="item-thumb">
                        <div>
                            <div class="item-title">{{ $item->title ?? $item->description }}</div>
                            @if($item->variant_title)
                                <div class="item-variant">{{ $item->variant_title }}</div>
                            @endif
                        </div>
                    </div>
                </td>
                <td align="center" class="value">{{ $item->quantity }}</td>
                <td align="right" class="value">{{ $item->price }}</td>
                <td align="right" class="value value-strong">{{ $item->total }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" align="right" class="text-muted">Subtotal</td>
                <td align="right" class="value">{{ $cart->sub_total }}</td>
            </tr>
            @if(!empty($cart->discount_total))
            <tr class="discount-row">
                <td colspan="3" align="right">Discount</td>
                <td align="right" class="value">-{{ $cart->discount_total }}</td>
            </tr>
            @endif
            @if(!empty($cart->tax_total))
            <tr>
                <td colspan="3" align="right" class="text-muted">Tax</td>
                <td align="right" class="value">{{ $cart->tax_total }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="3" align="right">Total</td>
                <td align="right" class="total-amount">{{ $cart->grand_total }}</td>
            </tr>
        </tfoot>
    </table>

    <p style="font-size:14px;line-height:1.6;margin:0 0 16px;">Complete your purchase now before these items sell out!</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center" style="margin:20px auto;text-align:center;width:100%">
        <tr>
            <td align="center">
                <a href="{{ $cart->recovery_url }}" target="_blank" rel="noopener" class="btn btn-success" style="padding:0 24px;">Complete Your Order</a>
            </td>
        </tr>
    </table>

    <p style="font-size:14px;line-height:1.6;margin:0 0 16px;">If you have any questions, please don't hesitate to contact us.</p>

    <p style="font-size:14px;line-height:1.5em;text-align:left;">Best regards,<br>The {{ $app->name }} Team</p>
</div>
