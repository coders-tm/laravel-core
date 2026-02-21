<?php

namespace Coderstm\Enum;

enum LogType: string
{
    case CREATED = 'created';
    case DELETED = 'deleted';
    case PERMANENTLY_DELETED = 'permanently_deleted';
    case UPDATED = 'updated';
    case RESTORED = 'restored';
}
