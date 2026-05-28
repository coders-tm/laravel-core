<div>
    <p>Hello,</p>
    <p>A member's subscription has been <strong>cancelled</strong>. Summary below:</p>

    <h3 class="section-title" style="margin-top:0">Subscription Summary</h3>
    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation">
        <tbody>
            <tr><td class="label">Name</td><td class="value">{{ $user->name }}</td></tr>
            <tr><td class="label">Email</td><td class="value">{{ $user->email }}</td></tr>
            <tr><td class="label">Phone</td><td class="value">{{ $user->phone_number ?? 'N/A' }}</td></tr>
            <tr><td class="label">Plan</td><td class="value">{{ $plan->label ?? 'N/A' }}</td></tr>
            <tr><td class="label">Price</td><td class="value">{{ $plan->price ?? '' }}</td></tr>
            <tr><td class="label">Ends At</td><td class="value">{{ $expires_at ?: $ends_at }}</td></tr>
        </tbody>
    </table>

    <p style="font-size:13px;line-height:1.6;margin:0 0 16px;" class="text-muted">Please update internal records. If the member returns, ensure a smooth reactivation experience.</p>

    <p style="font-size:14px;line-height:1.5em;text-align:left;">Regards,<br>{{ $app->name }}</p>

    <table class="panel panel-neutral" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="panel-content">
                <p class="small-note">Cancellation date reflects the recorded subscription end. If this was accidental, initiate a manual reactivation.</p>
            </td>
        </tr>
    </table>
</div>
