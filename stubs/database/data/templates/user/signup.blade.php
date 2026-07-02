<table cellpadding="0" cellspacing="0" role="presentation" width="100%"><tr><td>
    <p>Hi <b>{{ $user->first_name }}</b>,</p>
    <p>Welcome to {{ $app->name }}!</p>

    @if ($subscription)
        <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Your Subscription Details</p>
        <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;margin-bottom:16px;font-size:14px;">
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Plan</td>
                <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $subscription->plan->label }}</td>
            </tr>
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Price</td>
                <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $subscription->plan->price }}</td>
            </tr>
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Billing Cycle</td>
                <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $subscription->billing_cycle }}</td>
            </tr>
            @if (!empty($subscription->next_billing_date))
                <tr>
                    <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Next Billing Date</td>
                    <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $subscription->next_billing_date }}</td>
                </tr>
            @endif
        </table>
    @else
        <p style="font-size:14px;line-height:1.5em;">Your account is now active.</p>
    @endif

    <p style="font-size:14px;line-height:1.5em;color:#6b7280;">
        Questions? Contact us at
        <a href="mailto:{{ config('coderstm.admin_email') }}"
            style="color:#3869d4;">{{ config('coderstm.admin_email') }}</a>.
    </p>

    <p style="font-size:14px;line-height:1.5em;text-align:left;">Best regards,<br>{{ $app->name }}</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
        <tr>
            <td style="background:#edf2f7;color:#718096;padding:12px;font-size:14px;line-height:1.5;">
                <p style="font-size:12px;line-height:1.5;margin:0;color:#6b7280;">Welcome aboard!</p>
            </td>
        </tr>
    </table>
</td></tr></table>
