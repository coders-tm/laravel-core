<table cellpadding="0" cellspacing="0" role="presentation" width="100%"><tr><td>
    <p>Hi <b>{{ $user->first_name }}</b>,</p>
    <p>Thanks for contacting us. Your ticket has been created.</p>

    <h3 style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Ticket Information</h3>
    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;margin-bottom:16px;font-size:14px;">
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Ticket #</td>
            <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $enquiry->id }}</td>
        </tr>
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Subject</td>
            <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $enquiry->subject ?? '—' }}</td>
        </tr>
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Status</td>
            <td style="padding:12px;font-size:14px;color:#111827;vertical-align:middle;">{{ $enquiry->status }}</td>
        </tr>
    </table>

    @if (!empty($enquiry->message))
        <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
            <tr>
                <td style="background:#edf2f7;color:#718096;padding:12px;font-size:14px;line-height:1.5;">
                    {!! $enquiry->message !!}
                </td>
            </tr>
        </table>
    @endif

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

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px auto;text-align:center;" align="center">
        <tr>
            <td align="center">
                <a href="{{ $enquiry->url }}" target="_blank" rel="noopener" style="border-radius:4px;color:#ffffff;display:inline-block;text-decoration:none;padding:0 12px;font-size:14px;line-height:28px;font-weight:500;background:#2d3748;">View Ticket</a>
            </td>
        </tr>
    </table>

    <p style="font-size:14px;line-height:1.6;margin:0 0 16px;color:#6b7280;">We'll notify you when a team member replies.</p>

    <p style="font-size:14px;line-height:1.5em;text-align:left;">Regards,<br>{{ $app->name }}</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px;">
        <tr>
            <td>
                <p style="font-size:14px;line-height:1.5;color:#6b7280;margin:0;">
                    If you're having trouble clicking the "View Ticket" button, copy and paste this URL:
                    <span style="word-break:break-all;"><a href="{{ $enquiry->url }}" style="color:#3869d4;" target="_blank">{{ $enquiry->url }}</a></span>
                </p>
            </td>
        </tr>
    </table>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
        <tr>
            <td style="background:#edf2f7;color:#718096;padding:12px;font-size:14px;line-height:1.5;">
                <p style="font-size:12px;line-height:1.5;margin:0;color:#6b7280;">Please do not reply to this email; respond via the portal for fastest support.</p>
            </td>
        </tr>
    </table>
</td></tr></table>
