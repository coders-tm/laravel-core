<table cellpadding="0" cellspacing="0" role="presentation" width="100%"><tr><td>
    <p>Hi <b>Admin</b>,</p>
    <p>A refund has been processed for order <b>{{ $order->number }}</b>.</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
        <tr>
            <td style="background:#dbeafe;color:#1e40af;padding:12px;font-size:14px;line-height:1.5;">
                <strong>{{ $refund->status ?? 'Processed' }}</strong><br>
                Refund Amount: <strong>{{ $refund->amount ?? '' }}</strong>
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
    </table>

    <h3 style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Refund Details</h3>
    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;margin-bottom:16px;font-size:14px;">
        <tr><td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Refund Amount</td><td style="padding:12px;font-size:14px;font-weight:600;color:#111827;vertical-align:middle;">{{ $refund->amount ?? '' }}</td></tr>
        <tr><td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Refund Status</td><td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $refund->status ?? '' }}</td></tr>
        <tr><td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Payment Method</td><td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $refund->payment_method['name'] ?? 'N/A' }}</td></tr>
        <tr><td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Refund Date</td><td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ now()->format('M d, Y') }}</td></tr>
    </table>

    @if(!empty($refund->reason))
        <h3 style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Refund Reason</h3>
        <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
            <tr>
                <td style="background:#edf2f7;color:#718096;padding:12px;font-size:14px;line-height:1.5;">
                    {!! nl2br(e($refund->reason)) !!}
                </td>
            </tr>
        </table>
    @endif

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px auto;text-align:center;" align="center">
        <tr>
            <td align="center">
                <a href="{{ admin_url($order->url) }}" target="_blank" rel="noopener" style="border-radius:4px;color:#ffffff;display:inline-block;text-decoration:none;padding:0 12px;font-size:14px;line-height:28px;font-weight:500;background:#3869d4;">View Order in Admin</a>
            </td>
        </tr>
    </table>

    <p style="font-size:14px;line-height:1.6;margin:0 0 16px;color:#6b7280;">The customer has been notified of this refund. Please monitor the refund processing in your payment gateway.</p>

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
