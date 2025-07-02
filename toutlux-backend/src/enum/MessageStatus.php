<?php

namespace App\Enum;

enum MessageStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case MODIFIED = 'modified';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'En attente de modération',
            self::APPROVED => 'Approuvé',
            self::REJECTED => 'Rejeté',
            self::MODIFIED => 'Modifié par l\'admin',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::MODIFIED => 'info',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::PENDING => 'clock',
            self::APPROVED => 'check',
            self::REJECTED => 'x',
            self::MODIFIED => 'edit',
        };
    }

    public function canBeSent(): bool
    {
        return in_array($this, [self::APPROVED, self::MODIFIED], true);
    }
}
