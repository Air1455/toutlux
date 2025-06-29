<?php

namespace App\Enum;

enum EmailLogStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case FAILED = 'failed';
    case ARCHIVED = 'archived';
}
