<div>
    <p>Hi <b>{{ $user->first_name }}</b>,</p>
    <p>Welcome to {{ $app->name }}! We're thrilled to have you as a valued member of our community.</p>

    @if ($subscription)
        <p style="font-size:13px;font-weight:600;color:#111827;margin:20px 0 10px;">Your Subscription Details</p>
        <table class="card-table" cellpadding="0" cellspacing="0" role="presentation" width="100%">
            <tr>
                <td class="label">Plan</td>
                <td class="value">{{ $subscription->plan->label }}</td>
            </tr>
            <tr>
                <td class="label">Price</td>
                <td class="value">{{ $subscription->plan->price }}</td>
            </tr>
            <tr>
                <td class="label">Billing Cycle</td>
                <td class="value">{{ $subscription->billing_cycle }}</td>
            </tr>
            @if (!empty($subscription->next_billing_date))
                <tr>
                    <td class="label">Next Billing Date</td>
                    <td class="value">{{ $subscription->next_billing_date }}</td>
                </tr>
            @endif
        </table>

        <p style="font-size:14px;line-height:1.5em;margin-top:16px;">
            With this plan, you'll have access to exciting features, exclusive content, and premium benefits.
            We're confident you'll find great value throughout your subscription.
        </p>
    @else
        <p style="font-size:14px;line-height:1.5em;">
            Your account is now active and ready to use. Explore our features and discover what we have to offer!
        </p>
    @endif

    <p style="font-size:14px;line-height:1.5em;" class="text-muted">
        If you have any questions or need assistance, our support team is here to help at
        <a href="mailto:{{ config('coderstm.admin_email') }}"
            style="color:#3869d4;">{{ config('coderstm.admin_email') }}</a>.
    </p>

    <p style="font-size:14px;line-height:1.5em;text-align:left;">Best regards,<br>{{ $app->name }}</p>

    <table class="panel panel-neutral" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="panel-content">
                <p class="small-note">
                    Welcome aboard! We can't wait to see what you'll achieve with us.
                </p>
            </td>
        </tr>
    </table>
</div>
