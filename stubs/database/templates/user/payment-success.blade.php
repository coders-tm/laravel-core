<div>
    <p>Hi <b>{{ $order->customer->first_name ?? 'there' }}</b>,</p>

    <p>Great news! Your payment for order <b>{{ $order->number }}</b> has been successfully processed.</p>

    <p class="section-title">Payment Details</p>
    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tbody>
            <tr>
                <td class="label">Order Number</td>
                <td class="value">{{ $order->number }}</td>
            </tr>
            <tr>
                <td class="label">Payment Date</td>
                <td class="value">{{ now()->format('M d, Y') }}</td>
            </tr>
            @if(!empty($order->payments))
                <tr>
                    <td class="label">Payment Method</td>
                    <td class="value">{{ $order->payments[0]['payment_method']['name'] ?? 'N/A' }}</td>
                </tr>
                @if($order->payments[0]['transaction_id'])
                    <tr>
                        <td class="label">Transaction ID</td>
                        <td class="value">{{ $order->payments[0]['transaction_id'] }}</td>
                    </tr>
                @endif
                @if(!empty($order->payments[0]['payment_method']['provider_name']))
                    <tr>
                        <td class="label">Payment Provider</td>
                        <td class="value">{{ $order->payments[0]['payment_method']['provider_name'] }}</td>
                    </tr>
                @endif
            @endif
            <tr>
                <td class="label">Amount Paid</td>
                <td class="value value-strong">{{ $order->total }}</td>
            </tr>
            <tr>
                <td class="label">Payment Status</td>
                <td class="value success-row value-strong">{{ $order->payment_status }}</td>
            </tr>
        </tbody>
    </table>

    @if(count($order->payments ?? []) > 1)
        <p class="section-title">Payment History</p>
        <table class="card-table" cellpadding="0" cellspacing="0" role="presentation" width="100%">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Payment Method</th>
                    <th>Transaction ID</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->payments as $payment)
                    <tr>
                        <td>{{ $payment->date }}</td>
                        <td>{{ $payment->payment_method['name'] ?? 'N/A' }}</td>
                        <td>{{ $payment->transaction_id ?? 'N/A' }}</td>
                        <td class="value-strong">{{ $payment->amount }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="panel panel-success" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="panel-content">
                <p style="font-size:13px;line-height:1.5;margin:0;">✅ Payment confirmed! Your order is being processed and will be shipped soon.</p>
            </td>
        </tr>
    </table>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center">
        <tr>
            <td align="center">
                <a href="{{ app_url($order->url) }}" target="_blank" rel="noopener" class="btn btn-primary">View Order Details</a>
            </td>
        </tr>
    </table>

    <p>If you have any questions about your order, please contact us at <a href="mailto:{{ $support->email }}">{{ $support->email }}</a>.</p>

    <p>Thank you for your business!<br>{{ $app->name }} Team</p>

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
