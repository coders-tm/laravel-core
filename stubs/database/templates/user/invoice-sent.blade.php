<div>
    <p>Hi <b>{{ $order->customer->first_name ?? 'there' }}</b>,</p>

    <p>Your invoice is ready. Here are the details:</p>

    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tbody>
            <tr>
                <td class="label">Invoice Number</td>
                <td class="value">{{ $order->number }}</td>
            </tr>
            @if($order->has_due)
                <tr>
                    <td class="label">Due Amount</td>
                    <td class="value value-strong">{{ $order->due_amount }}</td>
                </tr>
            @endif
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
            @endif
        </tbody>
    </table>

    @if(count($order->payments ?? []) > 1)
    <h3 class="section-title">Payment History</h3>
    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <thead>
            <tr>
                <th align="left">Date</th>
                <th align="left">Payment Method</th>
                <th align="left">Transaction ID</th>
                <th align="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->payments as $payment)
            <tr>
                <td>{{ $payment->date }}</td>
                <td>{{ $payment->payment_method['name'] ?? 'N/A' }}</td>
                <td>{{ $payment->transaction_id ?? 'N/A' }}</td>
                <td align="right" class="value-strong">{{ $payment->amount }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <h3 class="section-title" style="margin-top:0">Invoice Summary</h3>

    <table class="card-table order-items" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <thead>
            <tr>
                <th align="left">Item</th>
                <th align="center" width="60">Qty</th>
                <th align="right" width="80">Price</th>
                <th align="right" width="100">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td class="item-row">
                    <div class="item-wrap">
                        <img src="{{ $item->thumbnail ?? 'https://placehold.co/80x80?text=No+Image' }}" alt="{{ $item->title }}" class="item-thumb">
                        <div>
                            <div class="item-title">{{ $item->title }}</div>
                            @if($item->variant_title)
                                <div class="item-variant">{{ $item->variant_title }}</div>
                            @endif
                        </div>
                    </div>
                </td>
                <td align="center" class="value">{{ $item->quantity }}</td>
                <td align="right" class="value">{{ $item->price }}</td>
                <td align="right" class="value value-strong">{{ $item->total }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" align="right" class="text-muted">Subtotal</td>
                <td align="right" class="value">{{ $order->sub_total }}</td>
            </tr>
            @if(!empty($order->discount_total))
            <tr class="discount-row">
                <td colspan="3" align="right">Discount</td>
                <td align="right" class="value">-{{ $order->discount_total }}</td>
            </tr>
            @endif
            @if(!empty($order->tax_total))
            <tr>
                <td colspan="3" align="right" class="text-muted">Tax</td>
                <td align="right" class="value">{{ $order->tax_total }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="3" align="right">Total</td>
                <td align="right" class="total-amount">{{ $order->total }}</td>
            </tr>
        </tfoot>
    </table>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center">
        <tr>
            <td align="center">
                <a href="{{ $order->payment_url }}" target="_blank" rel="noopener" class="btn btn-primary">View Invoice</a>
            </td>
        </tr>
    </table>

    <p>Best regards,<br>{{ $app->name }}</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="subcopy" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px">
        <tr>
            <td>
                <p class="text-muted">
                    If you're having trouble clicking the "View Invoice" button, copy and paste the URL below into your web browser:
                    <span class="break-all">
                        <a href="{{ $order->payment_url }}" target="_blank">{{ $order->payment_url }}</a>
                    </span>
                </p>
            </td>
        </tr>
    </table>
</div>
