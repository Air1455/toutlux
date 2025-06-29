<?php

namespace App\Enum;

enum EmailTemplate: string
{
    case WELCOME = 'welcome';
    case EMAIL_CONFIRMATION = 'email_confirmation';
    case EMAIL_CONFIRMED = 'email_confirmed';
    case RESET_PASSWORD = 'reset_password';
    case DOCUMENTS_APPROVED = 'documents_approved';
    case DOCUMENTS_REJECTED = 'documents_rejected';
    case NEW_MESSAGE = 'new_message';
    case ADMIN_REPLY = 'admin_reply';
    case TERMS_ACCEPTED = 'terms_accepted';
    case PASSWORD_CHANGED = 'password_changed';

    // Ajoute ici d'autres templates si besoin
}
