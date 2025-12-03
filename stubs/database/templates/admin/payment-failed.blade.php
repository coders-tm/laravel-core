<div>
    <p>Hi <b>Admin</b>,</p>
    <p>A payment failure has occurred for order <b>{{ $order->number }}</b>.</p>

    <table class="panel panel-danger" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="panel-content">
                <p style="margin:0;font-size:13px;line-height:1.5;">
                    ⚠️ <strong>Payment Failed</strong><br>
                    Reason: {{ $reason }}
                </p>
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
            <tr class="danger-row"><td class="label">Payment Status</td><td class="value value-strong">{{ $order->payment_status }}</td></tr>
        </tbody>
    </table>

    <h3 class="section-title">Recommended Actions</h3>
    <ul style="font-size:13px;line-height:1.7;margin:0 0 16px;">
        <li>Contact the customer to resolve the payment issue</li>
        <li>Verify payment method details with the customer</li>
        <li>Consider alternative payment methods</li>
        <li>Monitor for retry attempts</li>
    </ul>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center" style="margin:20px auto;text-align:center;width:100%">
        <tr>
            <td align="center">
                <a href="{{ admin_url($order->url) }}" target="_blank" rel="noopener" class="btn btn-danger" style="padding:0 16px;">View Order in Admin</a>
            </td>
        </tr>
    </table>

    <p style="font-size:14px;line-height:1.6;margin:0 0 16px;">This is an automated notification. Please review the order and take appropriate action.</p>

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
