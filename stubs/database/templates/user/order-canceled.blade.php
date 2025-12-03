<div>
    <p>Hi <b>{{ $order->customer->first_name }}</b>,</p>

    <table class="panel panel-warning" cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
        <tr>
            <td class="panel-content">
                <p style="font-size:12px;font-weight:600;margin:0 0 6px;">⚠️ Order Canceled</p>
                <p style="font-size:13px;line-height:1.4;margin:0;">Your order <b>{{ $order->number }}</b> has been canceled.</p>
            </td>
        </tr>
    </table>

    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="label">Order Number:</td>
            <td class="value">{{ $order->number }}</td>
        </tr>
        <tr>
            <td class="label">Order Date:</td>
            <td class="value">{{ $order->date }}</td>
        </tr>
        <tr>
            <td class="label">Total:</td>
            <td class="value">{{ $order->total }}</td>
        </tr>
        <tr>
            <td class="label">Status:</td>
            <td class="value danger-row">{{ $order->status }}</td>
        </tr>
    </table>

    @if ($order->refund_amount > 0)
        <table class="panel panel-info" cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
            <tr>
                <td class="panel-content">
                    <p style="font-size:12px;font-weight:600;margin:0 0 6px;">💳 Refund Processed</p>
                    <p style="font-size:13px;line-height:1.4;margin:0;">A refund of <b>{{ $order->refund_total }}</b> has been processed and will appear in your account within 5-10 business days.</p>
                </td>
            </tr>
        </table>
    @endif

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center" style="margin:20px auto;text-align:center;width:100%">
        <tr>
            <td align="center">
                <a href="{{ app_url($order->url) }}" target="_blank" rel="noopener" class="btn btn-dark" style="padding:0 16px;">View Order Details</a>
            </td>
        </tr>
    </table>

    <p>If you have any questions about this cancellation, please contact our support team.</p>

    <p style="font-size:14px;line-height:1.5em;text-align:left;">Best regards,<br>{{ $app->name }}</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="subcopy" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px">
        <tr>
            <td>
                <p style="line-height:1.5em;text-align:left;font-size:13px">
                    If you're having trouble clicking the "View Order Details" button, copy and paste the URL below into your web browser:
                    <span class="break-all"><a href="{{ app_url($order->url) }}" target="_blank" style="color:#3869d4">{{ app_url($order->url) }}</a></span>
                </p>
            </td>
        </tr>
    </table>
</div>
