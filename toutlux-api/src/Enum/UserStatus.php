<?php

namespace App\Enum;

enum UserStatus: string
{
    case PENDING_VERIFICATION = 'pending_verification';
    case EMAIL_CONFIRMED = 'email_confirmed';
    case DOCUMENTS_APPROVED = 'documents_approved';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
}
