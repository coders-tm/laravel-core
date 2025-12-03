<div>
    <p>Hi <b>{{ $order->customer->first_name ?? 'there' }}</b>,</p>

    <p>A partial refund has been processed for your order <b>{{ $order->number }}</b>.</p>

    <table class="panel panel-warning" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="panel-content">
                <p style="font-size:12px;font-weight:600;margin:0 0 6px;">💰 Partial Refund Issued</p>
                <p style="font-size:13px;line-height:1.4;margin:0;">Refund Amount: <strong>{{ $refund->amount }}</strong></p>
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
                <td class="label">Order Total</td>
                <td class="value">{{ $order->total }}</td>
            </tr>
            <tr>
                <td class="label">Refund Amount</td>
                <td class="value value-strong">{{ $refund->amount }}</td>
            </tr>
            <tr>
                <td class="label">Remaining Balance</td>
                <td class="value">{{ $refund->remaining_balance }}</td>
            </tr>
            <tr>
                <td class="label">Payment Method</td>
                <td class="value">{{ $refund->payment_method['name'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Refund Date</td>
                <td class="value">{{ now()->format('M d, Y') }}</td>
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

    <p class="section-title">What This Means</p>
    <p>This is a partial refund. You will receive <strong>{{ $refund->amount }}</strong> back to your original payment method. The remaining balance of <strong>{{ $refund->remaining_balance }}</strong> represents the portion of your order that will not be refunded.</p>

    <p class="section-title">Processing Time</p>
    <p>Please allow 5–10 business days for the refund to appear in your account, depending on your bank's processing time.</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center">
        <tr>
            <td align="center">
                <a href="{{ app_url($order->url) }}" target="_blank" rel="noopener" class="btn btn-primary">View Order Details</a>
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
