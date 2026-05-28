<div>
    <p>Hi <b>{{ $admin->first_name }}</b>,</p>

    <p>Welcome aboard! Your staff account has been created. Here are your login details:</p>

    <p class="section-title" style="margin-top:0">Account Details</p>
    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation">
        <tbody>
            <tr>
                <td class="label">Email</td>
                <td class="value">{{ $admin->email }}</td>
            </tr>
            <tr>
                <td class="label">Temporary Password</td>
                <td class="value value-strong">{{ $password }}</td>
            </tr>
        </tbody>
    </table>

    <p class="text-muted">For security, please log in and change your password immediately.</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center" style="margin:20px auto;text-align:center;width:100%">
        <tr>
            <td align="center">
                <a href="{{ $login_url }}" target="_blank" rel="noopener" class="btn btn-dark">Login Now</a>
            </td>
        </tr>
    </table>

    <p class="text-muted">
        If you have any questions or need assistance, please contact support.
    </p>

    <p>Best regards,<br>{{ $app->name }}</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="subcopy"
        style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px">
        <tr>
            <td>
                <p class="text-muted">
                    If you're having trouble clicking the "Login Now" button, copy and paste this URL into your
                    browser:<br>
                    <span class="break-all">
                        <a href="{{ $login_url }}" target="_blank">{{ $login_url }}</a>
                    </span>
                </p>
            </td>
        </tr>
    </table>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="panel panel-neutral">
        <tr>
            <td class="panel-content">
                <p class="small-note">This password was system-generated. If you did not expect this email, please notify an administrator.</p>
            </td>
        </tr>
    </table>
</div>
