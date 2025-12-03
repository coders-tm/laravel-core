<div>
    <p>Hi <b>Admin</b>,</p>
    <p>An order has been canceled by the customer.</p>

    <h3 class="section-title" style="margin-top:0">Order Information</h3>
    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation">
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
        </tbody>
    </table>

    @if($order->payment_status === 'Paid')
        <table class="panel panel-warning" cellpadding="0" cellspacing="0" role="presentation" width="100%">
            <tr>
                <td class="panel-content">
                    <p style="margin:0;font-size:13px;line-height:1.5;">
                        <strong>💰 Refund Required</strong><br>
                        This order was paid. A refund of <strong>{{ $order->total }}</strong> needs to be processed.
                    </p>
                </td>
            </tr>
        </table>
    @endif

    <h3 class="section-title">Canceled Items</h3>
    <table class="card-table order-items" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <thead>
            <tr>
                <th align="left">Item</th>
                <th align="center">Qty</th>
                <th align="right">Price</th>
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
                </tr>
            @endforeach
        </tbody>
    </table>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center" style="margin:20px auto;text-align:center;width:100%">
        <tr>
            <td align="center">
                <a href="{{ admin_url($order->url) }}" target="_blank" rel="noopener" class="btn btn-danger" style="padding:0 16px;">View Canceled Order</a>
            </td>
        </tr>
    </table>

    <p style="font-size:14px;line-height:1.6;margin:0 0 16px;">Please review this cancellation and process any necessary refunds through the admin panel.</p>

    <p style="font-size:14px;line-height:1.5em;text-align:left;">Regards,<br>{{ $app->name }} System</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="subcopy" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px">
        <tr>
            <td>
                <p style="line-height:1.5em;text-align:left;font-size:13px">
                    If you're having trouble clicking the button, copy and paste the URL below into your web browser:
                    <span class="break-all">
                        <a href="{{ admin_url($order->url) }}" target="_blank" style="color:#3869d4">{{ admin_url($order->url) }}</a>
                    </span>
                </p>
            </td>
        </tr>
    </table>

    <table class="panel panel-danger" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="panel-content">
                <p class="small-note">⚠️ This order has been canceled. Action may be required if payment was already processed.</p>
            </td>
        </tr>
    </table>
</div>
