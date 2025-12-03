<div>
    <p>Hi <b>{{ $order->customer->first_name ?? 'Customer' }}</b>,</p>

    <p>Your order <b>{{ $order->number }}</b> has been delivered! We hope you love your purchase. 📦✨</p>

    <p class="section-title">Delivery Confirmation</p>
    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tbody>
            <tr>
                <td class="label">Order Number</td>
                <td class="value">{{ $order->number }}</td>
            </tr>
            <tr>
                <td class="label">Delivered Date</td>
                <td class="value">{{ $order->delivered_at }}</td>
            </tr>
            @if(!empty($order->tracking_number))
            <tr>
                <td class="label">Tracking Number</td>
                <td class="value" style="font-family: monospace;">{{ $order->tracking_number }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <p class="section-title">Delivered Items</p>
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

    <table class="panel panel-warning" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="panel-content">
                <p style="font-size:12px;font-weight:600;margin:0 0 6px;">📝 How was your experience?</p>
                <p style="font-size:13px;line-height:1.4;margin:0;">We'd love to hear from you! Your feedback helps us improve and serve you better.</p>
            </td>
        </tr>
    </table>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center">
        <tr>
            <td align="center">
                <a href="{{ app_url($order->url) }}" target="_blank" rel="noopener" class="btn btn-primary">View Order & Leave Review</a>
            </td>
        </tr>
    </table>

    <p>If you have any issues with your order, please contact our support team at <a href="mailto:{{ $app->support_email }}">{{ $app->support_email }}</a>. We're here to help!</p>

    <p>Thank you for choosing {{ $app->name }}. We hope to serve you again soon!</p>

    <p>Regards,<br>{{ $app->name }}</p>

    <table class="panel panel-success" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="panel-content">
                <p class="small-note">✓ Your order has been successfully delivered. If you did not receive this order, please contact us immediately.</p>
            </td>
        </tr>
    </table>
</div>
