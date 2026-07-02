<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <style type="text/css">
        body { margin:0; padding:0; -webkit-text-size-adjust:100%; }
        @media screen and (max-width:620px){ .container, .email-card { width:100% !important; } }
    </style>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;background-color:#f3f4f6;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f3f4f6;padding:40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="margin:0 auto 20px;">
                    <tr>
                        <td align="center" style="padding:20px 0;">
                            <h1 style="margin:0;color:#1f2937;font-size:24px;font-weight:600;">
                                {{ config('app.name') }}
                            </h1>
                        </td>
                    </tr>
                </table>

                <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="background:#ffffff;margin:0 auto;">
                    <tr>
                        <td style="padding:40px 30px;font-size:14px;line-height:1.5;">
                            {!! $htmlContent !!}
                        </td>
                    </tr>
                </table>

                <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="margin:20px auto 0;">
                    <tr>
                        <td align="center" style="padding:20px 0;">
                            <p style="margin:0;font-size:12px;color:#6b7280;">
                                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
