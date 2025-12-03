<div>
    <p>Hi <b>{{ $user->first_name }}</b>,</p>
    <p>We're sorry to see you go, but we've received your subscription cancellation request.</p>

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
            <td class="label">Access Until</td>
            <td class="value">{{ $expires_at ?: $ends_at }}</td>
        </tr>
    </table>

    <p style="font-size:14px;line-height:1.5em;margin-top:16px;">
        If you've changed your mind, you can reactivate your subscription anytime before it expires.
    </p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center"
        style="margin:30px auto;text-align:center;width:100%">
        <tr>
            <td align="center">
                <a href="{{ $billing_page }}" target="_blank" rel="noopener" class="btn btn-dark" style="padding:0 16px;">
                    Resume Subscription
                </a>
            </td>
        </tr>
    </table>

    <p style="font-size:14px;line-height:1.5em;" class="text-muted">
        If you have any questions, reach out to us at
        <a href="mailto:{{ config('coderstm.admin_email') }}"
            style="color:#3869d4;">{{ config('coderstm.admin_email') }}</a>.
    </p>

    <p style="font-size:14px;line-height:1.5em;text-align:left;">Best regards,<br>{{ $app->name }}</p>

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
