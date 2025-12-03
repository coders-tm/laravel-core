<div>
    <p>Hi <b>{{ $admin->first_name ?? 'Admin' }}</b>,</p>
    <div style="font-size:13px;line-height:1.6;">{!! $task->message ?? '' !!}</div>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="action" align="center" style="margin:20px auto;text-align:center;width:100%">
        <tr>
            <td align="center">
                <a href="{{ $task->url }}" target="_blank" rel="noopener" class="btn btn-dark" style="padding:0 16px;">Open Task</a>
            </td>
        </tr>
    </table>

    @if (count($task->attachments ?? []))
        <div class="mt-20 mb-16" style="margin-top:12px;">
            <div style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:6px;">Attachments</div>
            @foreach ($task->attachments as $media)
                <div style="font-size:12px;line-height:1.4;">
                    <svg style="width:10px;vertical-align:middle;margin-right:4px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                        <path d="M396.2 83.8c-24.4-24.4-64-24.4-88.4 0l-184 184c-42.1 42.1-42.1 110.3 0 152.4s110.3 42.1 152.4 0l152-152c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-152 152c-64 64-167.6 64-231.6 0s-64-167.6 0-231.6l184-184c46.3-46.3 121.3-46.3 167.6 0s46.3 121.3 0 167.6l-176 176c-28.6 28.6-75 28.6-103.6 0s-28.6-75 0-103.6l144-144c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-144 144c-6.7 6.7-6.7 17.7 0 24.4s17.7 6.7 24.4 0l176-176c24.4-24.4 24.4-64 0-88.4z" />
                    </svg>
                    <a href="{{ $media->url }}" style="color:#3869d4;text-decoration:none;">{{ $media->name }}</a>
                </div>
            @endforeach
        </div>
    @endif

    <p style="font-size:14px;line-height:1.5em;text-align:left;">Regards,<br>{{ $app->name }}</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="subcopy" style="border-top:1px solid #e8e5ef;margin-top:25px;padding-top:25px">
        <tr>
            <td>
                <p style="line-height:1.5em;text-align:left;font-size:13px">If you're having trouble clicking the "Open Task" button, copy and paste the URL below into your web browser:<br><span class="break-all"><a href="{{ $task->url }}" target="_blank" style="color:#3869d4">{{ $task->url }}</a></span></p>
            </td>
        </tr>
    </table>

    <table class="panel panel-neutral" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tr>
            <td class="panel-content">
                <p class="small-note">Please don't respond to this email; any response should be made using the admin portal.</p>
            </td>
        </tr>
    </table>
</div>
