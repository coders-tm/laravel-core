<div>
    <p>Hi <b>{{ $user->first_name }}</b>,</p>
    <p>Your subscription ended on {{ $expires_at ?: $ends_at }}.</p>

    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="label">Plan</td>
            <td class="value">{{ $plan->label }}</td>
        </tr>
        <tr>
            <td class="label">Price</td>
            <td class="value">{{ $plan->price }}</td>
        </tr>
        <tr>
            <td class="label">Billing Cycle</td>
            <td class="value">{{ $billing_cycle }}</td>
        </tr>
        <tr>
            <td class="label">Ended On</td>
            <td class="value">{{ $expires_at ?: $ends_at }}</td>
        </tr>
    </table>

    <p style="font-size:14px;line-height:1.5em;margin-top:16px;">
        As your subscription has ended, you no longer have access to premium features.
        We hope you enjoyed your time with us!
    </p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center"
        style="margin:30px auto;text-align:center;width:100%">
        <tr>
            <td align="center">
                <a href="{{ $billing_page }}" target="_blank" rel="noopener" class="btn btn-dark" style="padding:0 16px;">
                    Renew Subscription
                </a>
            </td>
        </tr>
    </table>

    <p style="font-size:14px;line-height:1.5em;" class="text-muted">
        Questions? Contact us at
        <a href="mailto:{{ config('coderstm.admin_email') }}"
            style="color:#3869d4;">{{ config('coderstm.admin_email') }}</a>.
    </p>

    <p style="font-size:14px;line-height:1.5em;text-align:left;">Best regards,<br><b>{{ $app->name }}</b></p>

    <table class="panel panel-neutral" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="panel-content">
                <p class="small-note">
                    Thank you for being a part of our service. We hope to see you again soon!
                </p>
            </td>
        </tr>
    </table>
</div>
