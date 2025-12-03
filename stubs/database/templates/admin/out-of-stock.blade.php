<div>
    <h3 class="section-title" style="margin-top:0">🚨 Out of Stock Alert</h3>
    <p style="font-size:13px;line-height:1.5;margin:0 0 12px;">The following product variant is <strong class="text-danger">OUT OF STOCK</strong> and unavailable for fulfillment.</p>
    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation">
        <tbody>
            <tr>
                <td class="label">Product</td>
                <td class="value">{{ $variant->product_name }}</td>
            </tr>
            <tr>
                <td class="label">Variant</td>
                <td class="value">{{ $variant->title }}</td>
            </tr>
            <tr>
                <td class="label">SKU</td>
                <td class="value">{{ $variant->sku }}</td>
            </tr>
            @if($inventory)
            <tr>
                <td class="label">Location</td>
                <td class="value">{{ $inventory->location_name }}</td>
            </tr>
            @endif
            <tr class="danger-row">
                <td class="label">Current Stock</td>
                <td class="value value-strong">0</td>
            </tr>
        </tbody>
    </table>
    @if(!empty($variant->options))
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:10px 12px;margin:0 0 16px;">
            <div style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:6px;">Variant Options</div>
            <ul style="list-style:none;padding:0;margin:0;">
                @foreach($variant->options as $opt)
                    <li style="font-size:12px;color:#374151;line-height:1.4;">{{ $opt['name'] }}: {{ $opt['value'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <p style="font-size:13px;line-height:1.5;margin:0 0 16px;" class="text-danger value-strong">Urgent: Replenish this inventory to resume order fulfillment.</p>
    <div class="text-center" style="margin:20px 0;">
        <a href="{{ $variant->admin_url }}" target="_blank" rel="noopener" class="btn btn-danger" style="padding:0 16px;">Manage Inventory</a>
    </div>
    <p style="font-size:13px;line-height:1.5;margin:0;">Regards,<br>{{ $app->name }} Inventory System</p>
</div>
