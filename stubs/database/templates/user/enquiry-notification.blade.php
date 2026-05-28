<div>
    <p>Hi <b>{{ $user->first_name }}</b>,</p>
    <div style="font-size:13px;line-height:1.6;margin:0 0 16px;">{!! $enquiry->message !!}</div>

    <h3 class="section-title" style="margin-top:0">Ticket Details</h3>
    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation">
        <tbody>
            <tr>
                <td class="label">Ticket #</td>
                <td class="value">{{ $enquiry->id }}</td>
            </tr>
            <tr>
                <td class="label">Subject</td>
                <td class="value">{{ $enquiry->subject }}</td>
            </tr>
            <tr>
                <td class="label">Status</td>
                <td class="value">{{ $enquiry->status }}</td>
            </tr>
        </tbody>
    </table>

    @if (!empty($enquiry->attachments))
        <div style="margin-top:12px;margin-bottom:16px;">
            <div style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:6px;">Attachments</div>
            @foreach ($enquiry->attachments as $file)
                <div style="font-size:12px;line-height:1.4;">
                    <a href="{{ $file->url }}" target="_blank" rel="noopener" style="color:#3869d4;text-decoration:none;">{{ $file->name }}</a>
                </div>
            @endforeach
        </div>
    @endif

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center" style="margin:20px auto;text-align:center;width:100%">
        <tr>
            <td align="center">
                <a href="{{ $enquiry->url }}" target="_blank" rel="noopener" class="btn btn-dark" style="padding:0 18px;">Reply Now</a>
            </td>
        </tr>
    </table>

    <p style="font-size:14px;line-height:1.5em;text-align:left;">Regards,<br>{{ $app->name }}</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="subcopy" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px">
        <tr>
            <td>
                <p style="line-height:1.5em;text-align:left;font-size:13px;margin:0;">
                    If you're having trouble clicking the "Reply Now" button, copy and paste this URL into your browser:
                    <span class="break-all"><a href="{{ $enquiry->url }}" target="_blank" style="color:#3869d4">{{ $enquiry->url }}</a></span>
                </p>
            </td>
        </tr>
    </table>

    <table class="panel panel-neutral" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="panel-content">
                <p class="small-note">Please don't respond to this email; respond via the portal.</p>
            </td>
        </tr>
    </table>
</div>
