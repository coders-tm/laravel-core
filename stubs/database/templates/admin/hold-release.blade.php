<div>
    <p>Hello,</p>

    <p>The hold placed on a member's subscription has been released and their access to <strong>{{ $app->name }}</strong> has been restored. Here are the details:</p>

    <p class="section-title" style="margin-top:0">Member Details</p>
    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation">
        <tbody>
            <tr>
                <td class="label">Name</td>
                <td class="value">{{ $user->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Member ID</td>
                <td class="value">{{ $user->id ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Email</td>
                <td class="value">{{ $user->email ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Phone</td>
                <td class="value">{{ $user->phone_number ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    <p>Please update the member's status in our system accordingly and ensure they have full access to the facilities and services.</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="panel panel-neutral">
        <tr>
            <td class="panel-content">
                <p class="small-note">This notification confirms an account hold release. No action is required if already processed.</p>
            </td>
        </tr>
    </table>

    <p>Best regards,<br><strong>{{ $app->name }}</strong></p>
</div>
