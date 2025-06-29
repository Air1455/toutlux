<?php

namespace App\Enum;

enum MessageType: string
{
    case USER_TO_ADMIN = 'user_to_admin';
    case ADMIN_TO_USER = 'admin_to_user';
    // Pour support de conversation threadée :
    case USER_TO_USER = 'user_to_user';
    // Pour notification système :
    case SYSTEM = 'system';
}
