<?php

namespace App\Enum;

enum MessageType: string
{
    case SYSTEM = 'system';
    case USER_TO_ADMIN = 'user_to_admin';
    case ADMIN_TO_USER = 'admin_to_user';
}
