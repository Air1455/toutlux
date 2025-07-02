<?php

namespace App\Enum;

enum DocumentStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::APPROVED => 'Approuvé',
            self::REJECTED => 'Rejeté',
            self::EXPIRED => 'Expiré',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::EXPIRED => 'secondary',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::PENDING => 'clock',
            self::APPROVED => 'check-circle',
            self::REJECTED => 'x-circle',
            self::EXPIRED => 'alert-circle',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::PENDING;
    }

    public function canBeValidated(): bool
    {
        return $this === self::PENDING;
    }
}
