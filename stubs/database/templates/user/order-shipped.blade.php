<div>
    <p>Hi <b>{{ $order->customer->first_name ?? 'Customer' }}</b>,</p>

    <p>Great news! Your order <b>{{ $order->number }}</b> has been shipped and is on its way to you! 🎉</p>

    <p class="section-title">Shipping Information</p>
    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tbody>
            <tr>
                <td class="label">Order Number</td>
                <td class="value">{{ $order->number }}</td>
            </tr>
            <tr>
                <td class="label">Shipped Date</td>
                <td class="value">{{ $order->shipped_at }}</td>
            </tr>
            @if(!empty($order->tracking_company))
            <tr>
                <td class="label">Carrier</td>
                <td class="value">{{ $order->tracking_company }}</td>
            </tr>
            @endif
            @if(!empty($order->tracking_number))
            <tr>
                <td class="label">Tracking Number</td>
                <td class="value" style="font-family: monospace;">{{ $order->tracking_number }}</td>
            </tr>
            @endif
            @if(!empty($order->estimated_delivery))
            <tr>
                <td class="label">Estimated Delivery</td>
                <td class="value">{{ $order->estimated_delivery }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    @if(!empty($order->tracking_url))
    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center">
        <tr>
            <td align="center">
                <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener" class="btn btn-success">Track Your Package</a>
            </td>
        </tr>
    </table>
    @endif

    <p class="section-title">Items in Your Shipment</p>
    <table class="card-table order-items" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <thead>
            <tr>
                <th align="left">Item</th>
                <th align="center" width="60">Qty</th>
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
            </tr>
            @endforeach
        </tbody>
    </table>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center">
        <tr>
            <td align="center">
                <a href="{{ app_url($order->url) }}" target="_blank" rel="noopener" class="btn btn-primary">View Order Details</a>
            </td>
        </tr>
    </table>

    <p>We'll notify you again when your package is delivered. Thank you for shopping with us!</p>

    <p>Regards,<br>{{ $app->name }}</p>

    @if(!empty($order->tracking_url))
    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="subcopy" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px">
        <tr>
            <td>
                <p class="text-muted">
                    If you're having trouble clicking the "Track Your Package" button, copy and paste the URL below into your web browser:
                    <span class="break-all">
                        <a href="{{ $order->tracking_url }}" target="_blank">{{ $order->tracking_url }}</a>
                    </span>
                </p>
            </td>
        </tr>
    </table>
    @endif
</div>
