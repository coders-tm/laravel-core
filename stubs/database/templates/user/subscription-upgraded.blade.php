<div>
    <p>Hi <b>{{ $user->first_name }}</b>,</p>

    <p>Congratulations! Your subscription has been upgraded successfully.</p>

    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="label">New Plan:</td>
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
        @if (!empty($next_billing_date))
            <tr>
                <td class="label">Next Billing Date:</td>
                <td>{{ $next_billing_date }}</td>
            </tr>
        @endif
    </table>

    <p class="text-muted">
        Questions about your upgrade? Contact us at
        <a href="mailto:{{ config('coderstm.admin_email') }}">{{ config('coderstm.admin_email') }}</a>.
    </p>

    <p>Best regards,<br>{{ $app->name }}</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="panel panel-neutral">
        <tr>
            <td class="panel-content">
                <p class="small-note">
                    Thank you for using our service!
                </p>
            </td>
        </tr>
    </table>
</div>
