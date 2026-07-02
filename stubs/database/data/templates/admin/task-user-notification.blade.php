<table cellpadding="0" cellspacing="0" role="presentation" width="100%"><tr><td>
    <p>Hi <b>{{ $admin->first_name ?? 'Admin' }}</b>,</p>
    <div style="font-size:14px;line-height:1.6;">{!! $task->message ?? '' !!}</div>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px auto;text-align:center;" align="center">
        <tr>
            <td align="center">
                <a href="{{ $task->url }}" target="_blank" rel="noopener" style="border-radius:4px;color:#ffffff;display:inline-block;text-decoration:none;padding:0 12px;font-size:14px;line-height:28px;font-weight:500;background:#2d3748;">Open Task</a>
            </td>
        </tr>
    </table>

    @if (count($task->attachments ?? []))
        <div style="margin-top:12px;margin-bottom:16px;">
            <div style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:6px;">Attachments</div>
            @foreach ($task->attachments as $media)
                <div style="font-size:12px;line-height:1.4;">
                    <a href="{{ $media->url }}" style="color:#3869d4;text-decoration:none;">{{ $media->name }}</a>
                </div>
            @endforeach
        </div>
    @endif

    <p style="font-size:14px;line-height:1.5em;text-align:left;">Regards,<br>{{ $app->name }}</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px;">
        <tr>
            <td>
                <p style="font-size:14px;line-height:1.5;color:#6b7280;margin:0;">If you're having trouble clicking the "Open Task" button, copy and paste this URL:<br><span style="word-break:break-all;"><a href="{{ $task->url }}" style="color:#3869d4;" target="_blank">{{ $task->url }}</a></span></p>
            </td>
        </tr>
    </table>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
        <tr>
            <td style="background:#edf2f7;color:#718096;padding:12px;font-size:14px;line-height:1.5;">
                <p style="font-size:12px;line-height:1.5;margin:0;color:#6b7280;">Please don't respond to this email; any response should be made using the admin portal.</p>
            </td>
        </tr>
    </table>
</td></tr></table>
