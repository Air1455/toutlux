<?php

namespace App\Enum;

enum MessageStatus: string
{
    case UNREAD = 'unread';
    case READ = 'read';
    case ARCHIVED = 'archived';
    // Pour les systèmes de modération/draft, ajoute si besoin :
    case PENDING = 'pending';
    case DELETED = 'deleted';
}
