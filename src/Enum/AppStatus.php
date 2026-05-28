<?php

namespace Coderstm\Enum;

enum AppStatus: string
{
    case ACTIVE = 'Active';
    case DEACTIVE = 'Deactive';
    case HOLD = 'Hold';
    case LOST = 'Lost';
    case PENDING = 'Pending';
    case REPLIED = 'Replied';
    case STAFF_REPLIED = 'Staff Replied';
    case COMPLETED = 'Completed';
    case ONGOING = 'Ongoing';
    case RESOLVED = 'Resolved';
}
