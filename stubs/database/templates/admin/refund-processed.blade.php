<div>
    <p>Hi <b>Admin</b>,</p>
    <p>A refund has been processed for order <b>{{ $order->number }}</b>.</p>

    <table class="panel panel-info" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="panel-content">
                <p style="margin:0;font-size:13px;line-height:1.5;">💰 <strong>{{ $refund->status ?? 'Processed' }}</strong><br>Refund Amount: <strong>{{ $refund->amount ?? '' }}</strong></p>
            </td>
        </tr>
    </table>

    <h3 class="section-title" style="margin-top:0">Order Information</h3>
    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation">
        <tbody>
            <tr><td class="label">Order Number</td><td class="value">{{ $order->number }}</td></tr>
            <tr><td class="label">Order Date</td><td class="value">{{ $order->date }}</td></tr>
            <tr><td class="label">Customer Name</td><td class="value">{{ $order->customer->name ?? 'N/A' }}</td></tr>
            <tr><td class="label">Customer Email</td><td class="value">{{ $order->customer->email ?? 'N/A' }}</td></tr>
            <tr><td class="label">Order Total</td><td class="value value-strong">{{ $order->total }}</td></tr>
        </tbody>
    </table>

    <h3 class="section-title">Refund Details</h3>
    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation">
        <tbody>
            <tr><td class="label">Refund Amount</td><td class="value value-strong">{{ $refund->amount ?? '' }}</td></tr>
            <tr><td class="label">Refund Status</td><td class="value">{{ $refund->status ?? '' }}</td></tr>
            <tr><td class="label">Payment Method</td><td class="value">{{ $refund->payment_method['name'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Refund Date</td><td class="value">{{ now()->format('M d, Y') }}</td></tr>
        </tbody>
    </table>

    @if(!empty($refund->reason))
        <h3 class="section-title">Refund Reason</h3>
        <table class="panel panel-neutral" cellpadding="0" cellspacing="0" role="presentation" width="100%">
            <tr>
                <td class="panel-content" style="font-size:13px;line-height:1.5;">
                    {!! nl2br(e($refund->reason)) !!}
                </td>
            </tr>
        </table>
    @endif

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center" style="margin:20px auto;text-align:center;width:100%">
        <tr>
            <td align="center">
                <a href="{{ admin_url($order->url) }}" target="_blank" rel="noopener" class="btn btn-primary" style="padding:0 16px;">View Order in Admin</a>
            </td>
        </tr>
    </table>

    <p style="font-size:14px;line-height:1.6;margin:0 0 16px;">The customer has been notified of this refund. Please monitor the refund processing in your payment gateway.</p>

    <p style="font-size:14px;line-height:1.5em;text-align:left;">Regards,<br>{{ $app->name }} System</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="subcopy" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px">
        <tr>
            <td>
                <p style="line-height:1.5em;text-align:left;font-size:13px">
                    If you're having trouble clicking the button, copy and paste the URL below into your web browser:
                    <span class="break-all"><a href="{{ admin_url($order->url) }}" target="_blank" style="color:#3869d4">{{ admin_url($order->url) }}</a></span>
                </p>
            </td>
        </tr>
    </table>
</div>
