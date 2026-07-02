<table cellpadding="0" cellspacing="0" role="presentation" width="100%"><tr><td>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.5;">Hi <b>{{ $order->customer->first_name ?? 'there' }}</b>,</p>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.5;">A refund has been processed for your order <b>{{ $order->number }}</b>.</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
        <tr>
            <td style="background:#dbeafe;color:#1e40af;padding:12px;font-size:14px;line-height:1.5;">
                <p style="font-size:12px;font-weight:600;margin:0 0 6px;">{{ $refund->status ?? 'Processed' }}</p>
                <p style="font-size:14px;line-height:1.4;margin:0;">Refund Amount: <strong>{{ $refund->amount ?? '' }}</strong></p>
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
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Refund Amount</td>
                <td style="padding:12px;font-size:14px;font-weight:600;color:#111827;vertical-align:middle;">{{ $refund->amount ?? '' }}</td>
            </tr>
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Refund Status</td>
                <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $refund->status ?? '' }}</td>
            </tr>
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Payment Method</td>
                <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $refund->payment_method['name'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Refund Date</td>
                <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ now()->format('F d, Y') }}</td>
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

    <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Processing Time</p>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.5;">The refund has been issued to your original payment method. Allow 5-10 business days for processing.</p>

    @if(!empty($order->items))
    <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Items Refunded</p>
    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#ffffff;border:1px solid #e5e7eb;margin-bottom:16px;font-size:14px;">
        <thead>
            <tr>
                <th align="left" style="padding:12px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;border-bottom:1px solid #e5e7eb;">Item</th>
                <th align="center" width="60" style="padding:12px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;border-bottom:1px solid #e5e7eb;">Qty</th>
                <th align="right" width="100" style="padding:12px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;border-bottom:1px solid #e5e7eb;">Refunded</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr style="border-bottom:1px solid #f3f4f6;">
                <td style="padding:0;vertical-align:middle;">
                    <table cellpadding="0" cellspacing="0" role="presentation" width="100%">
                        <tr>
                            <td width="76" style="padding:12px 0 12px 12px;vertical-align:middle;">
                                <img src="{{ $item->thumbnail ?? 'https://placehold.co/80x80?text=No+Image' }}" alt="{{ $item->title }}" width="64" height="64" style="display:block;border-radius:6px;border:1px solid #e5e7eb;">
                            </td>
                            <td style="padding:12px 12px 12px 0;vertical-align:middle;">
                                <div style="font-size:14px;font-weight:500;color:#111827;line-height:1.4;">{{ $item->title }}</div>
                                @if($item->variant_title)
                                    <div style="font-size:12px;color:#6b7280;margin-top:4px;">{{ $item->variant_title }}</div>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
                <td align="center" style="padding:16px 12px;vertical-align:middle;font-size:14px;color:#111827;">{{ $item->quantity }}</td>
                <td align="right" style="padding:16px 12px;vertical-align:middle;font-size:14px;color:#111827;">{{ $item->total }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px auto;text-align:center;" align="center">
        <tr>
            <td align="center">
                <a href="{{ app_url($order->url) }}" style="border-radius:4px;color:#ffffff;display:inline-block;text-decoration:none;padding:0 12px;font-size:14px;line-height:28px;font-weight:500;background:#2d3748;">View Order Details</a>
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
