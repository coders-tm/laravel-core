<div>
    <p>Hi <b>Admin</b>,</p>

    <p>A new order has been placed! 🎉</p>

    <p class="section-title">Order Information</p>
    <table class="card-table order-info" cellpadding="0" cellspacing="0" role="presentation">
        <tbody>
            <tr>
                <td class="label">Order Number</td>
                <td class="value">{{ $order->number }}</td>
            </tr>
            <tr>
                <td class="label">Order Date</td>
                <td class="value">{{ $order->date }}</td>
            </tr>
            <tr>
                <td class="label">Customer Name</td>
                <td class="value">{{ $order->customer->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Customer Email</td>
                <td class="value">{{ $order->customer->email ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Order Total</td>
                <td class="value value-strong">{{ $order->total }}</td>
            </tr>
            <tr>
                <td class="label">Payment Status</td>
                <td class="value">{{ $order->payment_status }}</td>
            </tr>
            <tr>
                <td class="label">Order Status</td>
                <td class="value">{{ $order->status }}</td>
            </tr>
        </tbody>
    </table>

    @if($order->has_due)
        <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; margin: 20px 0;">
            <p style="margin: 0;">
                <strong>⚠️ Payment Pending</strong><br>
                This order has an outstanding balance of <strong>{{ $order->due_amount }}</strong>.
            </p>
        </div>
    @endif

    <p class="section-title">Order Items</p>
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
            @foreach($order->items as $item)
            <tr>
                <td class="item-row">
                    <div class="item-wrap">
                        <img src="{{ $item->thumbnail ?? 'https://placehold.co/80x80?text=No+Image' }}" alt="{{ $item->title }}" class="item-thumb">
                        <div>
                            <div class="item-title">{{ $item->title }}</div>
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
                <td align="right" class="value">{{ $order->sub_total }}</td>
            </tr>
            @if(!empty($order->discount_total))
            <tr class="discount-row">
                <td colspan="3" align="right">Discount</td>
                <td align="right" class="value">-{{ $order->discount_total }}</td>
            </tr>
            @endif
            @if(!empty($order->tax_total))
            <tr>
                <td colspan="3" align="right" class="text-muted">Tax</td>
                <td align="right" class="value">{{ $order->tax_total }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="3" align="right">Total</td>
                <td align="right" class="total-amount">{{ $order->total }}</td>
            </tr>
        </tfoot>
    </table>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center">
        <tr>
            <td align="center">
                <a href="{{ admin_url($order->url) }}" target="_blank" rel="noopener" class="btn btn-dark">View Order in Admin Panel</a>
            </td>
        </tr>
    </table>

    <p>Regards,<br>{{ $app->name }} System</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="subcopy"
        style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px">
        <tr>
            <td>
                <p class="text-muted">
                    If you're having trouble clicking the button, copy and paste the URL below into your
                    web browser:
                    <span class="break-all">
                        <a href="{{ admin_url($order->url) }}" target="_blank">{{ admin_url($order->url) }}</a>
                    </span>
                </p>
            </td>
        </tr>
    </table>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="panel panel-neutral">
        <tr>
            <td class="panel-content">
                <p class="small-note">This is an automated notification. Please process this order in the admin panel.</p>
            </td>
        </tr>
    </table>
</div>
