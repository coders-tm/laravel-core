<table cellpadding="0" cellspacing="0" role="presentation" width="100%"><tr><td>
    <p>Hi <b>{{ $user->first_name }}</b>,</p>
    <p>Your subscription cancellation request has been received.</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;margin-bottom:16px;font-size:14px;">
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Plan</td>
            <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $plan->label }}</td>
        </tr>
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Price</td>
            <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $plan->price }}</td>
        </tr>
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Access Until</td>
            <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $expires_at ?: $ends_at }}</td>
        </tr>
    </table>

    <p style="font-size:14px;line-height:1.5em;margin-top:16px;">
        You can reactivate your subscription anytime before it expires.
    </p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px auto;text-align:center;" align="center">
        <tr>
            <td align="center">
                <a href="{{ $billing_page }}" target="_blank" rel="noopener" style="border-radius:4px;color:#ffffff;display:inline-block;text-decoration:none;padding:0 16px;font-size:14px;line-height:28px;font-weight:500;background:#2d3748;">
                    Resume Subscription
                </a>
            </td>
        </tr>
    </table>

    <p style="font-size:14px;line-height:1.5em;color:#6b7280;">
        Questions? Contact us at
        <a href="mailto:{{ config('coderstm.admin_email') }}"
            style="color:#3869d4;">{{ config('coderstm.admin_email') }}</a>.
    </p>

    <p style="font-size:14px;line-height:1.5em;text-align:left;">Best regards,<br>{{ $app->name }}</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
        <tr>
            <td style="background:#edf2f7;color:#718096;padding:12px;font-size:14px;line-height:1.5;">
                <p style="font-size:12px;line-height:1.5;margin:0;color:#6b7280;">Thank you for being a part of our service.</p>
            </td>
        </tr>
    </table>
</td></tr></table>
