<div>
    <p>Hi <b>{{ $user->first_name }}</b>,</p>

    <p>Your subscription expired on <b>{{ $expires_at }}</b>.</p>

    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="label">Plan:</td>
            <td>{{ $plan->label }}</td>
        </tr>
        <tr>
            <td class="label">Price:</td>
            <td>{{ $plan->price }}</td>
        </tr>
        <tr>
            <td class="label">Billing Cycle:</td>
            <td>{{ $billing_cycle }}</td>
        </tr>
        <tr>
            <td class="label">Expired On:</td>
            <td>{{ $expires_at }}</td>
        </tr>
    </table>

    <p>
        As your subscription has ended, you no longer have access to premium features.
        We hope you enjoyed your time with us!
    </p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center"
        style="margin:30px auto;text-align:center;width:100%">
        <tr>
            <td align="center">
                <a href="{{ $billing_page }}" target="_blank" rel="noopener" class="btn btn-dark">
                    Renew Subscription
                </a>
            </td>
        </tr>
    </table>

    <p class="text-muted">
        Questions? Contact us at
        <a href="mailto:{{ config('coderstm.admin_email') }}">{{ config('coderstm.admin_email') }}</a>.
    </p>

    <p>Best regards,<br><b>{{ $app->name }}</b></p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="panel panel-neutral">
        <tr>
            <td class="panel-content">
                <p class="small-note">
                    Thank you for being a valued subscriber. We look forward to serving you again soon.
                </p>
            </td>
        </tr>
    </table>
</div>
