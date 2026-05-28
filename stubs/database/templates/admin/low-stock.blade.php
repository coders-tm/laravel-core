<div>
    <h3 class="section-title" style="margin-top:0">⚠️ Low Stock Alert</h3>
    <p style="font-size:13px;line-height:1.5;margin:0 0 12px 0;">The following product variant is running low on stock and approaching the threshold.</p>
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
            <tr>
                <td class="label">Current Stock</td>
                <td class="value">{{ $variant->available_quantity }}</td>
            </tr>
            <tr>
                <td class="label">Threshold</td>
                <td class="value">{{ $threshold }}</td>
            </tr>
        </tbody>
    </table>
    @if(!empty($variant->options))
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:10px 12px;margin:0 0 16px 0;">
            <div style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:6px;">Variant Options</div>
            <ul style="list-style:none;padding:0;margin:0;">
                @foreach($variant->options as $opt)
                <li style="font-size:12px;color:#374151;line-height:1.4;">{{ $opt['name'] }}: {{ $opt['value'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <p style="font-size:13px;line-height:1.5;margin:0 0 16px 0;color:#b45309;font-weight:500;">Action Recommended: Schedule a restock to avoid stockouts.</p>
    <div style="text-align:center;margin:20px 0;">
        <a href="{{ $variant->admin_url }}" target="_blank" rel="noopener" class="btn btn-dark" style="font-weight:600;">View in Admin</a>
    </div>
    <p style="font-size:13px;line-height:1.5;margin:0;">Regards,<br>{{ $app->name }} Inventory System</p>
</div>
