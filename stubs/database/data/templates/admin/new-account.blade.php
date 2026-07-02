<table cellpadding="0" cellspacing="0" role="presentation" width="100%"><tr><td>
    <p>Hi <b>{{ $admin->first_name }}</b>,</p>

    <p>Welcome aboard! Your staff account has been created.</p>

    <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Account Details</p>
    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;margin-bottom:16px;font-size:14px;">
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Email</td>
            <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $admin->email }}</td>
        </tr>
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Temporary Password</td>
            <td style="padding:12px;font-size:14px;font-weight:600;color:#111827;vertical-align:middle;">{{ $password }}</td>
        </tr>
    </table>

    <p style="font-size:14px;line-height:1.6;margin:0 0 16px;color:#6b7280;">For security, please log in and change your password immediately.</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px auto;text-align:center;" align="center">
        <tr>
            <td align="center">
                <a href="{{ $login_url }}" target="_blank" rel="noopener" style="border-radius:4px;color:#ffffff;display:inline-block;text-decoration:none;padding:0 12px;font-size:14px;line-height:28px;font-weight:500;background:#2d3748;">Login Now</a>
            </td>
        </tr>
    </table>

    <p style="font-size:14px;line-height:1.6;margin:0 0 16px;color:#6b7280;">If you have any questions, please contact support.</p>

    <p>Best regards,<br>{{ $app->name }}</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px;">
        <tr>
            <td>
                <p style="font-size:14px;line-height:1.5;color:#6b7280;margin:0;">
                    If you're having trouble clicking the "Login Now" button, copy and paste this URL:
                    <span style="word-break:break-all;"><a href="{{ $login_url }}" style="color:#3869d4;" target="_blank">{{ $login_url }}</a></span>
                </p>
            </td>
        </tr>
    </table>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
        <tr>
            <td style="background:#edf2f7;color:#718096;padding:12px;font-size:14px;line-height:1.5;">
                <p style="font-size:12px;line-height:1.5;margin:0;color:#6b7280;">This password was system-generated. If you did not expect this email, please notify an administrator.</p>
            </td>
        </tr>
    </table>
</td></tr></table>
