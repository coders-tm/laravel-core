<div>
    <p>Hi <b>{{ $user->first_name }}</b>,</p>

    <p>Great news! Your subscription has been successfully renewed.</p>

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
        @if (!empty($next_billing_date))
            <tr>
                <td class="label">Next Billing Date:</td>
                <td>{{ $next_billing_date }}</td>
            </tr>
        @endif
    </table>

    <p>
        Thank you for your continued trust! Your renewed subscription ensures uninterrupted access to all premium
        features and benefits.
    </p>

    <p class="text-muted">
        Questions? We're here to help at
        <a href="mailto:{{ config('coderstm.admin_email') }}">{{ config('coderstm.admin_email') }}</a>.
    </p>

    <p>Best regards,<br>{{ $app->name }}</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="panel panel-neutral">
        <tr>
            <td class="panel-content">
                <p class="small-note">
                    Thank you for being a valued member of our community. We look forward to continuing to serve you.
                </p>
            </td>
        </tr>
    </table>
</div>
