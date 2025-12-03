<div>
    <p>Hi <b>{{ $user->name }}</b>,</p>

    <p>We noticed a new sign in to your account.</p>

    <p class="section-title">Login Details</p>
    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="label">Time</td>
            <td class="value">{{ $log->time }}</td>
        </tr>
        <tr>
            <td class="label">Device</td>
            <td class="value">{{ $log->device }}</td>
        </tr>
        @if(!empty($log->location))
        <tr>
            <td class="label">Location</td>
            <td class="value">{{ $log->location }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">IP Address</td>
            <td class="value">{{ $log->ip }}</td>
        </tr>
    </table>

    <p>If this was you, you can safely ignore this email.</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="panel panel-warning">
        <tr>
            <td class="panel-content">
                <p class="small-note">
                    If you did not sign in, please secure your account immediately by changing your password.
                </p>
            </td>
        </tr>
    </table>

    <p>Best regards,<br>{{ $app->name }}</p>
</div>
