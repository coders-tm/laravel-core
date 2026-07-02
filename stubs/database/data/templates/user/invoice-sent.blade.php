<table cellpadding="0" cellspacing="0" role="presentation" width="100%"><tr><td>
    <p>Hi <b>{{ $order->customer->first_name ?? 'there' }}</b>,</p>

    <p>Your invoice is ready. Here are the details:</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;margin-bottom:16px;font-size:14px;">
        <tbody>
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Invoice Number</td>
                <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $order->number }}</td>
            </tr>
            @if($order->has_due)
                <tr>
                    <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Due Amount</td>
                    <td style="padding:12px;font-size:14px;font-weight:600;color:#111827;vertical-align:middle;">{{ $order->due_amount }}</td>
                </tr>
            @endif
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
            @endif
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

    <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Invoice Summary</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;margin-bottom:16px;font-size:14px;">
        <thead>
            <tr>
                <th align="left" style="padding:12px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;border-bottom:1px solid #e5e7eb;">Item</th>
                <th align="center" width="60" style="padding:12px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;border-bottom:1px solid #e5e7eb;">Qty</th>
                <th align="right" width="80" style="padding:12px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;border-bottom:1px solid #e5e7eb;">Price</th>
                <th align="right" width="100" style="padding:12px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;border-bottom:1px solid #e5e7eb;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr style="border-bottom:1px solid #f3f4f6;">
                <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <img src="{{ $item->thumbnail ?? 'https://placehold.co/80x80?text=No+Image' }}" alt="{{ $item->title }}" style="width:40px;height:40px;border-radius:6px;object-fit:cover;">
                        <div>
                            <div style="font-size:14px;font-weight:600;color:#111827;margin:0;">{{ $item->title }}</div>
                            @if($item->variant_title)
                                <div style="font-size:12px;color:#6b7280;margin:4px 0 0;">{{ $item->variant_title }}</div>
                            @endif
                        </div>
                    </div>
                </td>
                <td align="center" style="padding:12px;font-size:14px;color:#111827;">{{ $item->quantity }}</td>
                <td align="right" style="padding:12px;font-size:14px;color:#111827;">{{ $item->price }}</td>
                <td align="right" style="padding:12px;font-size:14px;font-weight:600;color:#111827;">{{ $item->total }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="border-bottom:1px solid #f3f4f6;">
                <td colspan="3" align="right" style="padding:12px;font-size:14px;color:#6b7280;">Subtotal</td>
                <td align="right" style="padding:12px;font-size:14px;color:#111827;">{{ $order->sub_total }}</td>
            </tr>
            @if(!empty($order->discount_total))
            <tr style="border-bottom:1px solid #f3f4f6;color:#10b981;">
                <td colspan="3" align="right" style="padding:12px;font-size:14px;color:#10b981;">Discount</td>
                <td align="right" style="padding:12px;font-size:14px;color:#10b981;">-{{ $order->discount_total }}</td>
            </tr>
            @endif
            @if(!empty($order->tax_total))
            <tr style="border-bottom:1px solid #f3f4f6;">
                <td colspan="3" align="right" style="padding:12px;font-size:14px;color:#6b7280;">Tax</td>
                <td align="right" style="padding:12px;font-size:14px;color:#111827;">{{ $order->tax_total }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="3" align="right" style="padding:12px;font-size:14px;font-weight:700;color:#111827;">Total</td>
                <td align="right" style="padding:12px;font-size:14px;font-weight:700;color:#111827;">{{ $order->total }}</td>
            </tr>
        </tfoot>
    </table>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px auto;text-align:center;" align="center">
        <tr>
            <td align="center">
                <a href="{{ $order->payment_url }}" target="_blank" rel="noopener" style="border-radius:4px;color:#ffffff;display:inline-block;text-decoration:none;padding:0 12px;font-size:14px;line-height:28px;font-weight:500;background:#3869d4;">View Invoice</a>
            </td>
        </tr>
    </table>

    <p>Best regards,<br>{{ $app->name }}</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px;">
        <tr>
            <td>
                <p style="font-size:14px;line-height:1.5;color:#6b7280;margin:0;">
                    If you're having trouble clicking the "View Invoice" button, copy and paste this URL:
                    <span style="word-break:break-all;"><a href="{{ $order->payment_url }}" style="color:#3869d4;" target="_blank">{{ $order->payment_url }}</a></span>
                </p>
            </td>
        </tr>
    </table>
</td></tr></table>
