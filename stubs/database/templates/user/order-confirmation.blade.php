<div>
    <p>Hi <b>{{ $order->customer->first_name ?? 'there' }}</b>,</p>

    <p>Thank you for your order! We're happy to confirm that we've received your order <b>{{ $order->number }}</b>.</p>

    <p class="section-title">Order Summary</p>

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

    @if($order->has_due)
        <table class="card-table" cellpadding="0" cellspacing="0" role="presentation">
            <tbody>
                <tr>
                    <td class="label">Payment Status</td>
                    <td class="value">Pending</td>
                </tr>
                <tr>
                    <td class="label">Amount Due</td>
                    <td class="value value-strong">{{ $order->due_amount }}</td>
                </tr>
            </tbody>
        </table>

        <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center">
            <tr>
                <td align="center">
                    <a href="{{ $order->payment_url }}" target="_blank" rel="noopener" class="btn btn-dark">Complete Payment</a>
                    <a href="{{ app_url($order->url) }}" target="_blank" rel="noopener" class="btn btn-primary">View Order Details</a>
                </td>
            </tr>
        </table>
    @else
        <table class="card-table" cellpadding="0" cellspacing="0" role="presentation">
            <tbody>
                <tr>
                    <td class="label">Payment Status</td>
                    <td class="value">{{ $order->payment_status }}</td>
                </tr>
                @if(isset($order->payments) && count($order->payments) > 0)
                    <tr>
                        <td class="label">Payment Method</td>
                        <td class="value">{{ $order->payments[0]->payment_method['name'] ?? 'N/A' }}</td>
                    </tr>
                    @if($order->payments[0]->transaction_id)
                    <tr>
                        <td class="label">Transaction ID</td>
                        <td class="value">{{ $order->payments[0]->transaction_id }}</td>
                    </tr>
                    @endif
                @endif
            </tbody>
        </table>

        @if(count($order->payments ?? []) > 1)
            <p class="section-title">Payment History</p>
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

        <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center">
            <tr>
                <td align="center">
                    <a href="{{ app_url($order->url) }}" target="_blank" rel="noopener" class="btn btn-primary">View Order Details</a>
                </td>
            </tr>
        </table>
    @endif

    <p>We'll send you shipping confirmation when your items are on the way!</p>

    <p>Regards,<br>{{ $app->name }}</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="subcopy" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px">
        <tr>
            <td>
                <p class="text-muted">
                    If you're having trouble clicking the "View Order Details" button, copy and paste the URL below into your web browser:
                    <span class="break-all">
                        <a href="{{ app_url($order->url) }}" target="_blank">{{ app_url($order->url) }}</a>
                    </span>
                </p>
            </td>
        </tr>
    </table>
</div>
