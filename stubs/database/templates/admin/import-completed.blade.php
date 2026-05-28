<div>
    <p>Hi <b>{{ $user->first_name ?? ($user->name ?? 'System') }}</b>,</p>

    <p>Your <strong>{{ $import->model }}</strong> import has completed with status <strong>{{ $import->status }}</strong>.</p>

    <p class="section-title" style="margin-top:0">Import Summary</p>
    <table class="card-table" cellpadding="0" cellspacing="0" role="presentation">
        <tbody>
            <tr>
                <td class="label">Successfully Imported</td>
                <td class="value value-strong">{{ $import->successed }}</td>
            </tr>
            <tr>
                <td class="label">Failed</td>
                <td class="value text-danger">{{ $import->failed }}</td>
            </tr>
            <tr>
                <td class="label">Skipped</td>
                <td class="value text-muted">{{ $import->skipped }}</td>
            </tr>
            <tr>
                <td class="label">Total Processed</td>
                <td class="value">{{ $import->successed + $import->failed + $import->skipped }}</td>
            </tr>
        </tbody>
    </table>

    @if ($import->failed + $import->skipped > 0)
        <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="panel panel-danger">
            <tr>
                <td class="panel-content">
                    <p style="font-size:12px;line-height:1.4em;margin:0;">Some rows did not import successfully. Review the failed/skipped logs for details.</p>
                </td>
            </tr>
        </table>
    @endif

    <p>Regards,<br>{{ $app->name }}</p>

    <table cellpadding="0" cellspacing="0" role="presentation" width="100%" class="panel panel-neutral">
        <tr>
            <td class="panel-content">
                <p class="small-note">This summary is based only on counts provided at completion. Detailed error lines are available in the import log.</p>
            </td>
        </tr>
    </table>
</div>
