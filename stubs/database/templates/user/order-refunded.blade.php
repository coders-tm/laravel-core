<div>
    <p>Hi <b>{{ $order->customer->first_name ?? 'there' }}</b>,</p>

    <p>A refund has been processed for your order <b>{{ $order->number }}</b>.</p>

    <table class="panel panel-info" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="panel-content">
                <p style="font-size:12px;font-weight:600;margin:0 0 6px;">💰 {{ $refund->status ?? 'Processed' }}</p>
                <p style="font-size:13px;line-height:1.4;margin:0;">Refund Amount: <strong>{{ $refund->amount ?? '' }}</strong></p>
            </td>
        </tr>
    </table>

    <p class="section-title">Refund Details</p>
    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tbody>
            <tr>
                <td class="label">Order Number</td>
                <td class="value">{{ $order->number }}</td>
            </tr>
            <tr>
                <td class="label">Refund Amount</td>
                <td class="value value-strong">{{ $refund->amount ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Refund Status</td>
                <td class="value">{{ $refund->status ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Payment Method</td>
                <td class="value">{{ $refund->payment_method['name'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Refund Date</td>
                <td class="value">{{ now()->format('F d, Y') }}</td>
            </tr>
        </tbody>
    </table>

    @if(!empty($refund->reason))
    <p class="section-title">Refund Reason</p>
    <table class="panel panel-neutral" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="panel-content">
                <p style="font-size:13px;line-height:1.5;margin:0;">{{ $refund->reason }}</p>
            </td>
        </tr>
    </table>
    @endif

    <p class="section-title">Processing Time</p>
    <p>The refund has been issued to your original payment method. Please allow 5-10 business days for the refund to appear in your account, depending on your bank's processing time.</p>

    @if(!empty($order->items))
    <p class="section-title">Items Refunded</p>
    <table class="card-table order-items" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <thead>
            <tr>
                <th align="left">Item</th>
                <th align="center" width="60">Qty</th>
                <th align="right" width="100">Refunded</th>
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
                <td align="right" class="value">{{ $item->total }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center">
        <tr>
            <td align="center">
                <a href="{{ app_url($order->url) }}" target="_blank" rel="noopener" class="btn btn-dark">View Order Details</a>
            </td>
        </tr>
    </table>

    <p>If you have any questions about this refund, please contact us at <a href="mailto:{{ $support->email }}">{{ $support->email }}</a>.</p>

    <p>Thank you,<br>{{ $app->name }} Team</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="subcopy" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px">
        <tr>
            <td>
                <p class="text-muted">
                    If you're having trouble clicking the button, copy and paste the URL below into your web browser:
                    <span class="break-all">
                        <a href="{{ app_url($order->url) }}" target="_blank">{{ app_url($order->url) }}</a>
                    </span>
                </p>
            </td>
        </tr>
    </table>
</div>
