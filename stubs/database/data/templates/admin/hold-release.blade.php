<table cellpadding="0" cellspacing="0" role="presentation" width="100%"><tr><td>
    <p>Hello,</p>

    <p>The hold on a member's subscription has been released and their access to <strong>{{ $app->name }}</strong> has been restored.</p>

    <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Member Details</p>
    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;margin-bottom:16px;font-size:14px;">
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Name</td>
            <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $user->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Member ID</td>
            <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $user->id ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Email</td>
            <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $user->email ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Phone</td>
            <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $user->phone_number ?? 'N/A' }}</td>
        </tr>
    </table>

    <p style="font-size:14px;line-height:1.6;color:#6b7280;margin:0 0 16px;">Please update the member's status in our system accordingly.</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
        <tr>
            <td style="background:#edf2f7;color:#718096;padding:12px;font-size:14px;line-height:1.5;">
                <p style="font-size:12px;line-height:1.5;margin:0;color:#6b7280;">This notification confirms an account hold release. No action is required if already processed.</p>
            </td>
        </tr>
    </table>

    <p>Best regards,<br><strong>{{ $app->name }}</strong></p>
</td></tr></table>
