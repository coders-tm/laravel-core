<?php

namespace Coderstm\Policies;

use Coderstm\Models\ReportExport;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ReportExportPolicy
{
    public function view(Authenticatable $user, ReportExport $reportExport): bool
    {
        return $user->id === $reportExport->admin_id;
    }

    public function delete(Authenticatable $user, ReportExport $reportExport): bool
    {
        return $user->id === $reportExport->admin_id;
    }
}
