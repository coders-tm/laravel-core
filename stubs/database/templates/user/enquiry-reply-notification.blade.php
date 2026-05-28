<div>
    <p>Hi <b>{{ $user->first_name ?? 'there' }}</b>,</p>
    <p>Update on ticket <b>#{{ $enquiry->id }}</b> – {{ $enquiry->subject }}.</p>

    <table class="panel panel-neutral" cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
        <tr>
            <td class="panel-content">
                <p style="font-size:12px;font-weight:600;margin:0 0 8px;">---- Ticket Update ----</p>
                <div style="font-size:13px;line-height:1.5;">{!! $reply->message ?? '' !!}</div>
            </td>
        </tr>
    </table>

    @if (count($reply->attachments ?? []))
        <div style="margin-top:12px;margin-bottom:16px;">
            <div style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:6px;">Attachments</div>
            @foreach ($reply->attachments as $file)
                <div style="font-size:12px;line-height:1.4;">
                    <a href="{{ $file->url }}" target="_blank" rel="noopener" style="color:#3869d4;text-decoration:none;">{{ $file->name }}</a>
                </div>
            @endforeach
        </div>
    @endif

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center" style="margin:20px auto;text-align:center;width:100%">
        <tr>
            <td align="center">
                <a href="{{ $enquiry->url }}" target="_blank" rel="noopener" class="btn btn-dark" style="padding:0 16px;">View Ticket</a>
            </td>
        </tr>
    </table>

    <p style="font-size:13px;line-height:1.6;margin:0 0 16px;" class="text-muted">Respond via the portal for threaded history.</p>

    <p style="font-size:14px;line-height:1.5em;text-align:left;">Best regards,<br>{{ $app->name }}</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="subcopy" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px">
        <tr>
            <td>
                <p style="line-height:1.5em;text-align:left;font-size:13px">
                    If you're having trouble clicking the "View Ticket" button, copy and paste the URL below into your web browser:
                    <span class="break-all"><a href="{{ $enquiry->url }}" target="_blank" style="color:#3869d4">{{ $enquiry->url }}</a></span>
                </p>
            </td>
        </tr>
    </table>

    <table class="panel panel-neutral" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="panel-content">
                <p class="small-note">Please don't reply to this email; use the customer portal.</p>
            </td>
        </tr>
    </table>
</div>
