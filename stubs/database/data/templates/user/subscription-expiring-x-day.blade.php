<table cellpadding="0" cellspacing="0" role="presentation" width="100%"><tr><td>
    <p>Hi <b>{{ $user->first_name }}</b>,</p>

    <p>Your subscription will expire in <b>{{ $days_remaining }} day(s)</b> on <b>{{ $expires_at }}</b>.</p>

    @if ($days_remaining <= 2)
    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#fee2e2;border:1px solid #fecaca;margin-bottom:16px;font-size:14px;">
        <tr>
            <td style="padding:12px;font-size:14px;color:#991b1b;">
                <strong>⚠ Your subscription is expiring soon.</strong> Renew immediately to avoid losing access.
            </td>
        </tr>
    </table>
    @else
    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#fef3c7;border:1px solid #fde68a;margin-bottom:16px;font-size:14px;">
        <tr>
            <td style="padding:12px;font-size:14px;color:#92400e;">
                <strong>⏰ Don't forget to renew.</strong> Keep your access uninterrupted.
            </td>
        </tr>
    </table>
    @endif

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;margin-bottom:16px;font-size:14px;">
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Plan:</td>
            <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $plan->label }}</td>
        </tr>
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Price:</td>
            <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $plan->price }}</td>
        </tr>
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Billing Cycle:</td>
            <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $billing_cycle }}</td>
        </tr>
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Expires On:</td>
            <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $expires_at }}</td>
        </tr>
    </table>

    <p>Renew now to keep your access uninterrupted.</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px auto;text-align:center;" align="center">
        <tr>
            <td align="center">
                <a href="{{ $renew_url }}" target="_blank" rel="noopener" style="border-radius:4px;color:#ffffff;display:inline-block;text-decoration:none;padding:0 12px;font-size:14px;line-height:28px;font-weight:500;background:#3869d4;">
                    Renew Now
                </a>
            </td>
        </tr>
    </table>

    <p style="color:#6b7280;">
        Questions? Contact us at
        <a href="mailto:{{ config('coderstm.admin_email') }}" style="color:#3869d4;">{{ config('coderstm.admin_email') }}</a>.
    </p>

    <p>Best regards,<br><b>{{ $app->name }}</b></p>
</td></tr></table>
