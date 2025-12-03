<div>
    <p>Hi <b>{{ $checkout->first_name ?? 'there' }}</b>,</p>

    <p>You have items waiting in your checkout. We've prepared everything for you to complete your purchase!</p>

    <h3 class="section-title" style="margin-top:0">Order Summary</h3>

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
            @foreach($checkout->items as $item)
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
                <td align="right" class="value">{{ $checkout->sub_total }}</td>
            </tr>
            @if(!empty($checkout->discount_total))
            <tr class="discount-row">
                <td colspan="3" align="right">Discount</td>
                <td align="right" class="value">-{{ $checkout->discount_total }}</td>
            </tr>
            @endif
            @if(!empty($checkout->tax_total))
            <tr>
                <td colspan="3" align="right" class="text-muted">Tax</td>
                <td align="right" class="value">{{ $checkout->tax_total }}</td>
            </tr>
            @endif
            @if(!empty($checkout->shipping_total))
            <tr>
                <td colspan="3" align="right" class="text-muted">Shipping</td>
                <td align="right" class="value">{{ $checkout->shipping_total }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="3" align="right">Total</td>
                <td align="right" class="total-amount">{{ $checkout->grand_total }}</td>
            </tr>
        </tfoot>
    </table>

    <p style="font-size:14px;line-height:1.6;margin:16px 0;">This checkout link will be available for a limited time. Complete your purchase now to secure these items!</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center" style="margin:20px auto;text-align:center;width:100%">
        <tr>
            <td align="center">
                <a href="{{ $checkout->checkout_url }}" target="_blank" rel="noopener" class="btn btn-success" style="padding:0 24px;">Complete Your Purchase</a>
            </td>
        </tr>
    </table>

    <p style="font-size:14px;line-height:1.6;margin:0 0 16px;">If you have any questions or need assistance, please don't hesitate to contact us.</p>

    <p style="font-size:14px;line-height:1.5em;text-align:left;">Thank you,<br>The {{ $app->name }} Team</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="subcopy" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px">
        <tr>
            <td>
                <p class="text-muted">
                    If you're having trouble clicking the "Complete Your Purchase" button, copy and paste the URL below into your web browser:
                    <span class="break-all">
                        <a href="{{ $checkout->checkout_url }}" target="_blank">{{ $checkout->checkout_url }}</a>
                    </span>
                </p>
            </td>
        </tr>
    </table>
</div>
