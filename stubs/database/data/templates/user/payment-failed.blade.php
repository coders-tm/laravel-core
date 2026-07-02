<table cellpadding="0" cellspacing="0" role="presentation" width="100%"><tr><td>
    <p>Hi <b>{{ $order->customer->first_name ?? 'there' }}</b>,</p>

    <p>Your payment for order <b>{{ $order->number }}</b> could not be processed.</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
        <tr>
            <td style="background:#fee2e2;color:#991b1b;padding:12px;font-size:14px;line-height:1.5;">
                <p style="font-size:12px;font-weight:600;margin:0 0 6px;color:#991b1b;">Payment Failed</p>
                <p style="font-size:14px;line-height:1.4;margin:0;color:#991b1b;">Reason: {{ $reason }}</p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Order Details</p>
    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;margin-bottom:16px;font-size:14px;">
        <tbody>
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Order Number</td>
                <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $order->number }}</td>
            </tr>
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Order Date</td>
                <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $order->date }}</td>
            </tr>
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Amount Due</td>
                <td style="padding:12px;font-size:14px;font-weight:600;color:#111827;vertical-align:middle;">{{ $order->total }}</td>
            </tr>
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Payment Status</td>
                <td style="padding:12px;font-size:14px;font-weight:600;color:#dc2626;vertical-align:middle;">{{ $order->payment_status }}</td>
            </tr>
        </tbody>
    </table>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px auto;text-align:center;" align="center">
        <tr>
            <td align="center">
                <a href="{{ $order->payment_url }}" target="_blank" rel="noopener" style="border-radius:4px;color:#ffffff;display:inline-block;text-decoration:none;padding:0 12px;font-size:14px;line-height:28px;font-weight:500;background:#dc2626;">Retry Payment</a>
            </td>
        </tr>
    </table>

    <p>Questions? Contact us at <a href="mailto:{{ $support->email }}">{{ $support->email }}</a>.</p>

    <p>Best regards,<br>{{ $app->name }} Team</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px;">
        <tr>
            <td>
                <p style="font-size:14px;line-height:1.5;color:#6b7280;margin:0;">
                    If you're having trouble clicking the button, copy and paste this URL:
                    <span style="word-break:break-all;"><a href="{{ $order->payment_url }}" style="color:#3869d4;" target="_blank">{{ $order->payment_url }}</a></span>
                </p>
            </td>
        </tr>
    </table>
</td></tr></table>
