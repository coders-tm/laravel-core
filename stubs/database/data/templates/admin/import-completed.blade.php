<table cellpadding="0" cellspacing="0" role="presentation" width="100%"><tr><td>
    <p>Hi <b>{{ $user->first_name ?? ($user->name ?? 'System') }}</b>,</p>

    <p>Your <strong>{{ $import->model }}</strong> import has completed with status <strong>{{ $import->status }}</strong>.</p>

    <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#111827;">Import Summary</p>
    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;margin-bottom:16px;font-size:14px;">
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Successfully Imported</td>
            <td style="padding:12px;font-size:14px;font-weight:600;color:#111827;vertical-align:middle;">{{ $import->successed }}</td>
        </tr>
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Failed</td>
            <td style="padding:12px;font-size:14px;color:#dc2626;vertical-align:middle;">{{ $import->failed }}</td>
        </tr>
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Skipped</td>
            <td style="padding:12px;font-size:14px;color:#6b7280;vertical-align:middle;">{{ $import->skipped }}</td>
        </tr>
        <tr>
            <td width="35%" style="padding:12px;font-size:12px;font-weight:600;color:#6b7280;vertical-align:middle;">Total Processed</td>
            <td style="padding:12px;font-size:14px;font-weight:600;color:#111827;vertical-align:middle;">{{ $import->successed + $import->failed + $import->skipped }}</td>
        </tr>
    </table>

    @if ($import->failed + $import->skipped > 0)
        <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
            <tr>
                <td style="background:#fee2e2;color:#991b1b;padding:12px;font-size:14px;line-height:1.5;">
                    <p style="font-size:12px;line-height:1.4em;margin:0;">Some rows did not import successfully. Review the failed/skipped logs for details.</p>
                </td>
            </tr>
        </table>
    @endif

    <p>Regards,<br>{{ $app->name }}</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin:20px 0;">
        <tr>
            <td style="background:#edf2f7;color:#718096;padding:12px;font-size:14px;line-height:1.5;">
                <p style="font-size:12px;line-height:1.5;margin:0;color:#6b7280;">This summary is based only on counts provided at completion. Detailed error lines are available in the import log.</p>
            </td>
        </tr>
    </table>
</td></tr></table>
