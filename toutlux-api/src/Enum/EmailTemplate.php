<?php

namespace App\Enum;

enum EmailTemplate: string
{
    case WELCOME = 'welcome';
    case EMAIL_CONFIRMED = 'email_confirmed';
    case DOCUMENTS_APPROVED = 'documents_approved';
    case NEW_MESSAGE = 'new_message';
    case ADMIN_REPLY = 'admin_reply';
}
