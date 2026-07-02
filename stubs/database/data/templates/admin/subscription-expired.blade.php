<table cellpadding="0" cellspacing="0" role="presentation" width="100%"><tr><td>
    <p>Hello,</p>
    <p>A member's subscription has <strong>expired</strong>. Details below:</p>

    <h3 style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Subscription Details</h3>
    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;margin-bottom:16px;font-size:14px;">
        <tr><td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Name</td><td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $user->name }}</td></tr>
        <tr><td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Email</td><td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $user->email }}</td></tr>
        <tr><td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Phone</td><td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $user->phone_number ?? 'N/A' }}</td></tr>
        <tr><td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Plan</td><td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $plan->label ?? 'N/A' }}</td></tr>
        <tr><td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Price</td><td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $plan->price ?? '' }}</td></tr>
        <tr><td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Expired At</td><td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $expires_at ?: $ends_at }}</td></tr>
    </table>

    <p style="font-size:14px;line-height:1.6;margin:0 0 16px;color:#6b7280;">If the member renews, ensure timely reactivation and billing alignment.</p>

    <p style="font-size:14px;line-height:1.5em;text-align:left;">Regards,<br>{{ $app->name }}</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
        <tr>
            <td style="background:#edf2f7;color:#718096;padding:12px;font-size:14px;line-height:1.5;">
                <p style="font-size:12px;line-height:1.5;margin:0;color:#6b7280;">Expiration indicates the subscription naturally reached its term. For grace-period scenarios, verify whether a late renewal is still permitted.</p>
            </td>
        </tr>
    </table>
</td></tr></table>
