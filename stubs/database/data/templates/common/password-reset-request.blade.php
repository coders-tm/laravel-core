<table cellpadding="0" cellspacing="0" role="presentation" width="100%"><tr><td>
    <p>Hi <b>{{ $user->name }}</b>,</p>

    <p>We received a request to reset your password.</p>

    @if(!empty($reset->url))
        <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px auto;text-align:center;" align="center">
            <tr>
                <td align="center">
                    <a href="{{ $reset->url }}" target="_blank" rel="noopener" style="border-radius:4px;color:#ffffff;display:inline-block;text-decoration:none;padding:0 12px;font-size:14px;line-height:28px;font-weight:500;background:#3869d4;">Reset Password</a>
                </td>
            </tr>
        </table>

        <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px;">
            <tr>
                <td>
                    <p style="font-size:14px;line-height:1.5;color:#6b7280;margin:0;">
                        If you're having trouble clicking the "Reset Password" button, copy and paste this URL:
                        <span style="word-break:break-all;"><a href="{{ $reset->url }}" style="color:#3869d4;" target="_blank">{{ $reset->url }}</a></span>
                    </p>
                </td>
            </tr>
        </table>
    @else
        <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Reset Token</p>
        <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;margin-bottom:16px;font-size:14px;">
            <tr>
                <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Token</td>
                <td style="padding:12px;font-size:14px;font-weight:600;color:#111827;vertical-align:middle;">{{ $reset->token }}</td>
            </tr>
        </table>
    @endif

    @if(!empty($reset->expires))
        <p style="font-size:12px;line-height:1.5;margin:0;color:#6b7280;">This reset option will expire in {{ $reset->expires }} minutes.</p>
    @endif

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
        <tr>
            <td style="background:#edf2f7;color:#718096;padding:12px;font-size:14px;line-height:1.5;">
                <p style="font-size:12px;line-height:1.5;margin:0;color:#6b7280;">If you did not request a password reset, you can safely ignore this email.</p>
            </td>
        </tr>
    </table>

    <p>Best regards,<br>{{ $app->name }}</p>
</td></tr></table>
