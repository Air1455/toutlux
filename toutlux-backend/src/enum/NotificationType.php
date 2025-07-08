<?php

namespace App\Enum;

enum NotificationType: string
{
    // Account notifications
    case WELCOME = 'welcome';
    case EMAIL_VERIFICATION = 'email_verification';
    case EMAIL_VERIFIED = 'email_verified';
    case PASSWORD_CHANGED = 'password_changed';
    case PROFILE_UPDATED = 'profile_updated';

    // Property status change notification (ajouté)
    case PROPERTY_STATUS_CHANGE = 'property_status_change';

    // Document notifications
    case DOCUMENT_VALIDATED = 'document_validated';
    case DOCUMENT_REJECTED = 'document_rejected';
    case DOCUMENT_EXPIRED = 'document_expired';
    case DOCUMENT_EXPIRING_SOON = 'document_expiring_soon';

    // Message notifications
    case NEW_MESSAGE = 'new_message';
    case MESSAGE_APPROVED = 'message_approved';
    case MESSAGE_REJECTED = 'message_rejected';

    // Property notifications
    case PROPERTY_VIEWED = 'property_viewed';
    case PROPERTY_FAVORITED = 'property_favorited';
    case PROPERTY_INQUIRY = 'property_inquiry';

    // Admin notifications
    case ADMIN_NOTICE = 'admin_notice';
    case SYSTEM_MAINTENANCE = 'system_maintenance';

    public function label(): string
    {
        return match($this) {
            self::WELCOME => 'Bienvenue',
            self::EMAIL_VERIFICATION => 'Vérification email',
            self::EMAIL_VERIFIED => 'Email vérifié',
            self::PASSWORD_CHANGED => 'Mot de passe modifié',
            self::PROFILE_UPDATED => 'Profil mis à jour',

            self::PROPERTY_STATUS_CHANGE => 'Changement de statut de propriété', // Ajouté

            self::DOCUMENT_VALIDATED => 'Document validé',
            self::DOCUMENT_REJECTED => 'Document rejeté',
            self::DOCUMENT_EXPIRED => 'Document expiré',
            self::DOCUMENT_EXPIRING_SOON => 'Document bientôt expiré',

            self::NEW_MESSAGE => 'Nouveau message',
            self::MESSAGE_APPROVED => 'Message approuvé',
            self::MESSAGE_REJECTED => 'Message rejeté',

            self::PROPERTY_VIEWED => 'Propriété consultée',
            self::PROPERTY_FAVORITED => 'Propriété ajoutée aux favoris',
            self::PROPERTY_INQUIRY => 'Demande sur votre propriété',

            self::ADMIN_NOTICE => 'Information administrative',
            self::SYSTEM_MAINTENANCE => 'Maintenance système',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::WELCOME => 'user-plus',
            self::EMAIL_VERIFICATION, self::EMAIL_VERIFIED => 'mail',
            self::PASSWORD_CHANGED => 'key',
            self::PROFILE_UPDATED => 'user-check',

            self::PROPERTY_STATUS_CHANGE => 'refresh-cw', // Ajouté (ou autre icône pertinente)

            self::DOCUMENT_VALIDATED => 'file-check',
            self::DOCUMENT_REJECTED => 'file-x',
            self::DOCUMENT_EXPIRED, self::DOCUMENT_EXPIRING_SOON => 'clock',

            self::NEW_MESSAGE => 'message-circle',
            self::MESSAGE_APPROVED => 'message-check',
            self::MESSAGE_REJECTED => 'message-x',

            self::PROPERTY_VIEWED => 'eye',
            self::PROPERTY_FAVORITED => 'heart',
            self::PROPERTY_INQUIRY => 'help-circle',

            self::ADMIN_NOTICE => 'info',
            self::SYSTEM_MAINTENANCE => 'tool',
        };
    }

    public function category(): string
    {
        return match($this) {
            self::WELCOME,
            self::EMAIL_VERIFICATION,
            self::EMAIL_VERIFIED,
            self::PASSWORD_CHANGED,
            self::PROFILE_UPDATED => 'account',

            self::PROPERTY_STATUS_CHANGE => 'property', // Ajouté ici

            self::DOCUMENT_VALIDATED,
            self::DOCUMENT_REJECTED,
            self::DOCUMENT_EXPIRED,
            self::DOCUMENT_EXPIRING_SOON => 'document',

            self::NEW_MESSAGE,
            self::MESSAGE_APPROVED,
            self::MESSAGE_REJECTED => 'message',

            self::PROPERTY_VIEWED,
            self::PROPERTY_FAVORITED,
            self::PROPERTY_INQUIRY => 'property',

            self::ADMIN_NOTICE,
            self::SYSTEM_MAINTENANCE => 'system',
        };
    }

    public function priority(): string
    {
        return match($this) {
            self::EMAIL_VERIFICATION,
            self::DOCUMENT_REJECTED,
            self::MESSAGE_REJECTED,
            self::DOCUMENT_EXPIRED,
            self::SYSTEM_MAINTENANCE => 'high',

            self::NEW_MESSAGE,
            self::PROPERTY_INQUIRY,
            self::DOCUMENT_EXPIRING_SOON,
            self::PROPERTY_STATUS_CHANGE => 'medium', // Ajouté ici (ou 'low' selon ton besoin)

            default => 'low',
        };
    }

    public function shouldSendEmail(): bool
    {
        return match($this) {
            self::WELCOME,
            self::EMAIL_VERIFICATION,
            self::DOCUMENT_VALIDATED,
            self::DOCUMENT_REJECTED,
            self::NEW_MESSAGE,
            self::MESSAGE_APPROVED,
            self::MESSAGE_REJECTED,
            self::PROPERTY_INQUIRY,
            self::PROPERTY_STATUS_CHANGE => true, // Ajouté ici si tu veux envoyer un email, sinon mets false

            default => false,
        };
    }
}
