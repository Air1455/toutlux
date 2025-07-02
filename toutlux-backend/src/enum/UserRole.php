<?php

namespace App\Enum;

enum UserRole: string
{
    case USER = 'ROLE_USER';
    case ADMIN = 'ROLE_ADMIN';
    case SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public function label(): string
    {
        return match($this) {
            self::USER => 'Utilisateur',
            self::ADMIN => 'Administrateur',
            self::SUPER_ADMIN => 'Super Administrateur',
        };
    }

    public function getHierarchy(): array
    {
        return match($this) {
            self::USER => ['ROLE_USER'],
            self::ADMIN => ['ROLE_USER', 'ROLE_ADMIN'],
            self::SUPER_ADMIN => ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'],
        };
    }
}
