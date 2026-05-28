<div>
    <p>Hi <b>{{ $order->customer->first_name ?? 'there' }}</b>,</p>

    <p>We're sorry to inform you that your payment for order <b>{{ $order->number }}</b> could not be processed.</p>

    <table class="panel panel-danger" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="panel-content">
                <p style="font-size:12px;font-weight:600;margin:0 0 6px;">⚠️ Payment Failed</p>
                <p style="font-size:13px;line-height:1.4;margin:0;">Reason: {{ $reason }}</p>
            </td>
        </tr>
    </table>

    <p class="section-title">Order Details</p>
    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation" width="100%">
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
                <td class="label">Amount Due</td>
                <td class="value value-strong">{{ $order->total }}</td>
            </tr>
            <tr>
                <td class="label">Payment Status</td>
                <td class="value danger-row value-strong">{{ $order->payment_status }}</td>
            </tr>
        </tbody>
    </table>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center">
        <tr>
            <td align="center">
                <a href="{{ $order->payment_url }}" target="_blank" rel="noopener" class="btn btn-danger">Retry Payment</a>
            </td>
        </tr>
    </table>

    <p>If you continue to experience issues, please contact our support team at <a href="mailto:{{ $support->email }}">{{ $support->email }}</a>.</p>

    <p>Best regards,<br>{{ $app->name }} Team</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="subcopy" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px">
        <tr>
            <td>
                <p class="text-muted">
                    If you're having trouble clicking the button, copy and paste the URL below into your web browser:
                    <span class="break-all">
                        <a href="{{ $order->payment_url }}" target="_blank">{{ $order->payment_url }}</a>
                    </span>
                </p>
            </td>
        </tr>
    </table>
</div>
