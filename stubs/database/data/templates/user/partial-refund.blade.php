<table cellpadding="0" cellspacing="0" role="presentation" width="100%"><tr><td>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.5;">Hi <b>{{ $order->customer->first_name ?? 'there' }}</b>,</p>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.5;">A partial refund has been processed for your order <b>{{ $order->number }}</b>.</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
        <tr>
            <td style="background:#fef3c7;color:#92400e;padding:12px;font-size:14px;line-height:1.5;">
                <p style="font-size:12px;font-weight:600;margin:0 0 6px;">Partial Refund Issued</p>
                <p style="font-size:14px;line-height:1.4;margin:0;">Refund Amount: <strong>{{ $refund->amount }}</strong></p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Refund Details</p>
    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;margin-bottom:16px;font-size:14px;">
        <tbody>
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Order Number</td>
                <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $order->number }}</td>
            </tr>
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Order Total</td>
                <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $order->total }}</td>
            </tr>
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Refund Amount</td>
                <td style="padding:12px;font-size:14px;font-weight:600;color:#111827;vertical-align:middle;">{{ $refund->amount }}</td>
            </tr>
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Remaining Balance</td>
                <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $refund->remaining_balance }}</td>
            </tr>
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Payment Method</td>
                <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $refund->payment_method['name'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Refund Date</td>
                <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ now()->format('M d, Y') }}</td>
            </tr>
        </tbody>
    </table>

    @if(!empty($refund->reason))
    <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Refund Reason</p>
    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
        <tr>
            <td style="background:#edf2f7;color:#718096;padding:12px;font-size:14px;line-height:1.5;">
                <p style="font-size:14px;line-height:1.5;margin:0;">{{ $refund->reason }}</p>
            </td>
        </tr>
    </table>
    @endif

    <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">What This Means</p>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.5;">You will receive <strong>{{ $refund->amount }}</strong> back to your original payment method. The remaining balance of <strong>{{ $refund->remaining_balance }}</strong> will not be refunded.</p>

    <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Processing Time</p>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.5;">Allow 5-10 business days for processing.</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px auto;text-align:center;" align="center">
        <tr>
            <td align="center">
                <a href="{{ app_url($order->url) }}" style="border-radius:4px;color:#ffffff;display:inline-block;text-decoration:none;padding:0 12px;font-size:14px;line-height:28px;font-weight:500;background:#3869d4;">View Order Details</a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px;font-size:14px;line-height:1.5;">Contact us at <a href="mailto:{{ $support->email }}" style="color:#3869d4;">{{ $support->email }}</a> if you have questions about this refund.</p>

    <p style="margin:0 0 16px;font-size:14px;line-height:1.5;">Thank you,<br>{{ $app->name }} Team</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px;">
        <tr>
            <td>
                <p style="font-size:14px;line-height:1.5;color:#6b7280;margin:0;">
                    If you're having trouble clicking the button, copy and paste this URL:
                    <span style="word-break:break-all;"><a href="{{ app_url($order->url) }}" style="color:#3869d4;" target="_blank">{{ app_url($order->url) }}</a></span>
                </p>
            </td>
        </tr>
    </table>
</td></tr></table>
