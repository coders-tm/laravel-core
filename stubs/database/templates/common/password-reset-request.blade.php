<div>
    <p>Hi <b>{{ $user->name }}</b>,</p>

    <p>We received a request to reset your password. If you made this request, please use the option below to set a new password.</p>

    @if(!empty($reset->url))
        <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center" style="margin:20px auto;text-align:center;width:100%">
            <tr>
                <td align="center">
                    <a href="{{ $reset->url }}" target="_blank" rel="noopener" class="btn btn-primary">Reset Password</a>
                </td>
            </tr>
        </table>

        <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="subcopy"
            style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px">
            <tr>
                <td>
                    <p class="text-muted">
                        If you're having trouble clicking the "Reset Password" button, copy and paste this URL into your
                        browser:<br>
                        <span class="break-all">
                            <a href="{{ $reset->url }}" target="_blank">{{ $reset->url }}</a>
                        </span>
                    </p>
                </td>
            </tr>
        </table>
    @else
        <p class="section-title" style="margin-top:0">Reset Token</p>
        <table class="card-table" cellpadding="0" cellspacing="0" role="presentation">
            <tbody>
                <tr>
                    <td class="label">Token</td>
                    <td class="value value-strong">{{ $reset->token }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @if(!empty($reset->expires))
        <p class="small-note">This reset option will expire in {{ $reset->expires }} minutes.</p>
    @endif

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="panel panel-neutral">
        <tr>
            <td class="panel-content">
                <p class="small-note">If you did not request a password reset, you can safely ignore this email.</p>
            </td>
        </tr>
    </table>

    <p>Best regards,<br>{{ $app->name }}</p>
</div>
