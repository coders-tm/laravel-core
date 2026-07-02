<table cellpadding="0" cellspacing="0" role="presentation" width="100%"><tr><td>
    <p>Hi <b>Admin</b>,</p>
    <p>A payment failure has occurred for order <b>{{ $order->number }}</b>.</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
        <tr>
            <td style="background:#fee2e2;color:#991b1b;padding:12px;font-size:14px;line-height:1.5;">
                <strong>Payment Failed</strong><br>
                Reason: {{ $reason }}
            </td>
        </tr>
    </table>

    <h3 style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Order Information</h3>
    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;margin-bottom:16px;font-size:14px;">
        <tr><td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Order Number</td><td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $order->number }}</td></tr>
        <tr><td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Order Date</td><td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $order->date }}</td></tr>
        <tr><td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Customer Name</td><td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $order->customer->name ?? 'N/A' }}</td></tr>
        <tr><td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Customer Email</td><td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $order->customer->email ?? 'N/A' }}</td></tr>
        <tr><td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Order Total</td><td style="padding:12px;font-size:14px;font-weight:600;color:#111827;vertical-align:middle;">{{ $order->total }}</td></tr>
        <tr><td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Payment Status</td><td style="padding:12px;font-size:14px;font-weight:600;color:#dc2626;vertical-align:middle;">{{ $order->payment_status }}</td></tr>
    </table>

    <h3 style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Recommended Actions</h3>
    <ul style="font-size:14px;line-height:1.7;margin:0 0 16px;padding-left:20px;color:#111827;">
        <li>Contact the customer to resolve the payment issue</li>
        <li>Verify payment method details with the customer</li>
        <li>Consider alternative payment methods</li>
        <li>Monitor for retry attempts</li>
    </ul>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px auto;text-align:center;" align="center">
        <tr>
            <td align="center">
                <a href="{{ admin_url($order->url) }}" target="_blank" rel="noopener" style="border-radius:4px;color:#ffffff;display:inline-block;text-decoration:none;padding:0 12px;font-size:14px;line-height:28px;font-weight:500;background:#dc2626;">View Order in Admin</a>
            </td>
        </tr>
    </table>

    <p style="font-size:14px;line-height:1.6;margin:0 0 16px;color:#6b7280;">This is an automated notification. Please review the order and take appropriate action.</p>

    <p style="font-size:14px;line-height:1.5em;text-align:left;">Regards,<br>{{ $app->name }} System</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px;">
        <tr>
            <td>
                <p style="font-size:14px;line-height:1.5;color:#6b7280;margin:0;">
                    If you're having trouble clicking the button, copy and paste this URL:
                    <span style="word-break:break-all;"><a href="{{ admin_url($order->url) }}" style="color:#3869d4;" target="_blank">{{ admin_url($order->url) }}</a></span>
                </p>
            </td>
        </tr>
    </table>
</td></tr></table>
