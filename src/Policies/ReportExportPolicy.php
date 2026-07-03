<?php

namespace Coderstm\Policies;

use Coderstm\Models\ReportExport;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ReportExportPolicy
{
    /**
     * Determine whether the user can view the report export.
     */
    public function view(Authenticatable $user, ReportExport $reportExport): bool
    {
        // Admin can only view their own report exports
        return $user->id === $reportExport->admin_id;
    }

    /**
     * Determine whether the user can delete the report export.
     */
    public function delete(Authenticatable $user, ReportExport $reportExport): bool
    {
        // Admin can only delete their own report exports
        return $user->id === $reportExport->admin_id;
    }
}
