<table cellpadding="0" cellspacing="0" role="presentation" width="100%"><tr><td>
    <p>Hi <b>{{ $order->customer->first_name ?? 'there' }}</b>,</p>

    <p>Your payment for order <b>{{ $order->number }}</b> has been successfully processed.</p>

    <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Payment Details</p>
    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;margin-bottom:16px;font-size:14px;">
        <tbody>
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Order Number</td>
                <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $order->number }}</td>
            </tr>
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Payment Date</td>
                <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ now()->format('M d, Y') }}</td>
            </tr>
            @if(!empty($order->payments))
                <tr>
                    <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Payment Method</td>
                    <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $order->payments[0]['payment_method']['name'] ?? 'N/A' }}</td>
                </tr>
                @if($order->payments[0]['transaction_id'])
                    <tr>
                        <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Transaction ID</td>
                        <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $order->payments[0]['transaction_id'] }}</td>
                    </tr>
                @endif
                @if(!empty($order->payments[0]['payment_method']['provider_name']))
                    <tr>
                        <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Payment Provider</td>
                        <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $order->payments[0]['payment_method']['provider_name'] }}</td>
                    </tr>
                @endif
            @endif
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Amount Paid</td>
                <td style="padding:12px;font-size:14px;font-weight:600;color:#111827;vertical-align:middle;">{{ $order->total }}</td>
            </tr>
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Payment Status</td>
                <td style="padding:12px;font-size:14px;font-weight:600;color:#10b981;vertical-align:middle;">{{ $order->payment_status }}</td>
            </tr>
        </tbody>
    </table>

    @if(count($order->payments ?? []) > 1)
        <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Payment History</p>
        <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;margin-bottom:16px;font-size:14px;">
            <thead>
                <tr>
                    <th align="left" style="padding:12px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;border-bottom:1px solid #e5e7eb;">Date</th>
                    <th align="left" style="padding:12px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;border-bottom:1px solid #e5e7eb;">Payment Method</th>
                    <th align="left" style="padding:12px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;border-bottom:1px solid #e5e7eb;">Transaction ID</th>
                    <th align="right" style="padding:12px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;border-bottom:1px solid #e5e7eb;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->payments as $payment)
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <td style="padding:12px;font-size:14px;color:#111827;">{{ $payment->date }}</td>
                        <td style="padding:12px;font-size:14px;color:#111827;">{{ $payment->payment_method['name'] ?? 'N/A' }}</td>
                        <td style="padding:12px;font-size:14px;color:#111827;">{{ $payment->transaction_id ?? 'N/A' }}</td>
                        <td align="right" style="padding:12px;font-size:14px;font-weight:600;color:#111827;">{{ $payment->amount }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
        <tr>
            <td style="background:#d1fae5;color:#065f46;padding:12px;font-size:14px;line-height:1.5;">Payment confirmed! Your order is being processed and will be shipped soon.</td>
        </tr>
    </table>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px auto;text-align:center;" align="center">
        <tr>
            <td align="center">
                <a href="{{ app_url($order->url) }}" target="_blank" rel="noopener" style="border-radius:4px;color:#ffffff;display:inline-block;text-decoration:none;padding:0 12px;font-size:14px;line-height:28px;font-weight:500;background:#3869d4;">View Order Details</a>
            </td>
        </tr>
    </table>

    <p>Questions? Contact us at <a href="mailto:{{ $support->email }}">{{ $support->email }}</a>.</p>

    <p>Thank you for your business!<br>{{ $app->name }} Team</p>

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
