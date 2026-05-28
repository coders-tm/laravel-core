<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <style type="text/css">
        /* Core Layout */
        body { margin:0; padding:0; -webkit-text-size-adjust:100%; font-size:14px; }
        .container { width:600px; margin:0 auto; }
        .email-wrapper { background:#f3f4f6; padding:40px 0; }
        .email-card { background:#ffffff; border-radius:8px; box-shadow:0 1px 3px 0 rgba(0,0,0,.1); }
        .email-content { padding:40px 30px; }

        /* Typography */
        p { margin:0 0 16px; font-size:14px; line-height:1.5em; color:#1f2937; }
        p:last-child { margin-bottom:0; }
        b, strong { font-weight:600; }
        a { color:#3869d4; text-decoration:none; }
        .section-title { margin:20px 0 12px; font-size:13px; font-weight:600; color:#111827; }
        .small-note { font-size:11px; line-height:1.5em; margin:0; }
        .text-muted { color:#6b7280; }
        .text-success { color:#10b981; }
        .text-danger { color:#dc2626; }
        .text-warning { color:#f59e0b; }
        .text-primary { color:#3869d4; }

        /* Buttons */
        .btn { border-radius:4px; color:#fff !important; display:inline-block; text-decoration:none; padding:0 12px; border:6px solid transparent; font-size:13px; line-height:28px; height:28px; font-weight:500; }
        .btn-primary { background:#3869d4; border-color:#3869d4; }
        .btn-danger { background:#dc2626; border-color:#dc2626; }
        .btn-warning { background:#f59e0b; border-color:#f59e0b; }
        .btn-dark { background:#2d3748; border-color:#2d3748; }
        .btn-success { background:#10b981; border-color:#10b981; }

        /* Panel / Alerts */
        .panel { margin:20px 0; border-left:4px solid #e5e7eb; }
        .panel-content { padding:10px; font-size:14px; line-height:1.5em; text-align:left; margin:0; }
        .panel-info { border-left-color:#3869d4; }
        .panel-info .panel-content { background:#dbeafe; color:#1e40af; }
        .panel-success { border-left-color:#10b981; }
        .panel-success .panel-content { background:#d1fae5; color:#065f46; }
        .panel-warning { border-left-color:#f59e0b; }
        .panel-warning .panel-content { background:#fef3c7; color:#92400e; }
        .panel-danger { border-left-color:#dc2626; }
        .panel-danger .panel-content { background:#fee2e2; color:#991b1b; }
        .panel-neutral { border-left-color:#2d3748; }
        .panel-neutral .panel-content { background:#edf2f7; color:#718096; }

        /* Card Table */
        .card-table { width:100%; border-collapse:collapse; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; margin-bottom:16px; font-size:13px; }
        .card-table thead th { background:#e5e7eb; padding:12px; font-size:11px; font-weight:600; text-transform:uppercase; color:#6b7280; letter-spacing:.5px; }
        .card-table tbody tr { background:transparent; }
        .card-table tbody tr:last-child { border-bottom:none; }
        .card-table td { padding:12px; vertical-align:middle; }
        .card-table .label { font-size:12px; font-weight:600; color:#6b7280; width:35%; }
        .card-table .value { font-size:13px; color:#111827; }
        .card-table .value-strong { font-weight:600; }
        .card-table tfoot { background:#e5e7eb; }
        .card-table tfoot td { font-size:13px; padding:10px 12px; background:transparent; }
        .card-table tfoot tr.total-row td { background:transparent; padding:12px; font-size:14px; font-weight:700; color:#111827; }
        .card-table tfoot tr.total-row td.total-amount { font-size:16px; font-weight:700; }
        .discount-row .value, .discount-row td:last-child { color:#059669; font-weight:600; }
        .danger-row .value, .danger-row td:last-child { color:#dc2626; }
        .success-row .value, .success-row td:last-child { color:#10b981; }

        /* Item Row (for order items) */
        .item-row { padding:0; }
        .item-row .item-wrap { display:table; width:100%; }
        .item-row .item-wrap > * { display:table-cell; vertical-align:middle; }
        .item-thumb { width:64px; height:64px; object-fit:cover; border-radius:6px; border:1px solid #e5e7eb; margin-right:12px; }
        .item-title { font-size:13px; font-weight:500; color:#111827; line-height:1.4; }
        .item-variant { font-size:12px; color:#6b7280; margin-top:4px; }

        /* Order Items Table - Specific Overrides */
        .order-items { background:#ffffff !important; border:1px solid #e5e7eb; }
        .order-items thead th { background:transparent !important; padding:12px; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#6b7280; border-bottom:1px solid #e5e7eb; }
        .order-items tbody tr { border-bottom:1px solid #f3f4f6; }
        .order-items tbody tr:last-child { border-bottom:none; }
        .order-items tbody td { padding:16px 12px; vertical-align:middle; }
        .order-items tfoot { background:transparent !important; border-top:1px solid #e5e7eb; }
        .order-items tfoot td { padding:10px 12px; font-size:13px; }
        .order-items tfoot tr.total-row { background:transparent !important; }
        .order-items tfoot tr.total-row td { padding:12px; font-weight:700; font-size:14px; }
        .order-items tfoot tr.total-row .total-amount { font-size:16px; color:#111827; }

        /* Utility */
        .action { margin-bottom:20px; text-align:center; }
        .mt-20 { margin-top:20px; }
        .mb-16 { margin-bottom:16px; }
        .text-center { text-align:center; }
        .break-all { word-break:break-all; }

        /* Fallback for clients stripping classes (optional minimal inline mimic) */
        @media screen and (max-width:620px){ .container, .email-card { width:100% !important; } .email-content { padding:24px 20px !important; } }
    </style>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f3f4f6;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f3f4f6; padding: 40px 0;">
        <tr>
            <td align="center">
                {{-- Header --}}
                <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 auto 20px;">
                    <tr>
                        <td align="center" style="padding: 20px 0;">
                            <h1 style="margin: 0; color: #1f2937; font-size: 24px; font-weight: 600;">
                                {{ config('app.name') }}
                            </h1>
                        </td>
                    </tr>
                </table>

                {{-- Main Content --}}
                <table width="600" cellpadding="0" cellspacing="0" role="presentation" class="email-card">
                    <tr>
                        <td class="email-content">
                            {!! $htmlContent !!}
                        </td>
                    </tr>
                </table>

                {{-- Footer --}}
                <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="margin: 20px auto 0;">
                    <tr>
                        <td align="center" style="padding: 20px 0;">
                            <p style="margin: 0; font-size: 13px; color: #6b7280;">
                                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
