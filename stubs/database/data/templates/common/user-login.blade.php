<table cellpadding="0" cellspacing="0" role="presentation" width="100%"><tr><td>
    <p>Hi <b>{{ $user->name }}</b>,</p>

    <p>We noticed a new sign in to your account.</p>

    <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Login Details</p>
    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;margin-bottom:16px;font-size:14px;">
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Time</td>
            <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $log->time }}</td>
        </tr>
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Device</td>
            <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $log->device }}</td>
        </tr>
        @if(!empty($log->location))
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Location</td>
            <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $log->location }}</td>
        </tr>
        @endif
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">IP Address</td>
            <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $log->ip }}</td>
        </tr>
    </table>

    <p>If this was you, you can safely ignore this email.</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
        <tr>
            <td style="background:#fef3c7;color:#92400e;padding:12px;font-size:14px;line-height:1.5;">
                <p style="font-size:12px;line-height:1.5;margin:0;color:#6b7280;">If you did not sign in, please secure your account immediately by changing your password.</p>
            </td>
        </tr>
    </table>

    <p>Best regards,<br>{{ $app->name }}</p>
</td></tr></table>
