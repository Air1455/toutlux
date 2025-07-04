<?php

namespace App\Enum;

enum DocumentType: string
{
    case IDENTITY_CARD = 'identity_card';
    case PASSPORT = 'passport';
    case DRIVER_LICENSE = 'driver_license';
    case SELFIE_WITH_ID = 'selfie_with_id';
    case PROOF_OF_INCOME = 'proof_of_income';
    case BANK_STATEMENT = 'bank_statement';
    case PAYSLIP = 'payslip';
    case TAX_RETURN = 'tax_return';
    case EMPLOYMENT_CONTRACT = 'employment_contract';

    public function label(): string
    {
        return match($this) {
            self::IDENTITY_CARD => 'Carte d\'identité',
            self::PASSPORT => 'Passeport',
            self::DRIVER_LICENSE => 'Permis de conduire',
            self::SELFIE_WITH_ID => 'Selfie avec pièce d\'identité',
            self::PROOF_OF_INCOME => 'Justificatif de revenus',
            self::BANK_STATEMENT => 'Relevé bancaire',
            self::PAYSLIP => 'Bulletin de salaire',
            self::TAX_RETURN => 'Avis d\'imposition',
            self::EMPLOYMENT_CONTRACT => 'Contrat de travail',
        };
    }

    public function category(): string
    {
        return match($this) {
            self::IDENTITY_CARD,
            self::PASSPORT,
            self::DRIVER_LICENSE,
            self::SELFIE_WITH_ID => 'identity',

            self::PROOF_OF_INCOME,
            self::BANK_STATEMENT,
            self::PAYSLIP,
            self::TAX_RETURN,
            self::EMPLOYMENT_CONTRACT => 'financial',
        };
    }

    public function isIdentityDocument(): bool
    {
        return $this->category() === 'identity';
    }

    public function isFinancialDocument(): bool
    {
        return $this->category() === 'financial';
    }

    public static function getIdentityTypes(): array
    {
        return array_filter(
            self::cases(),
            fn($type) => $type->isIdentityDocument()
        );
    }

    public static function getFinancialTypes(): array
    {
        return array_filter(
            self::cases(),
            fn($type) => $type->isFinancialDocument()
        );
    }

    public function getMaxSize(): int
    {
        // Retourne la taille max en bytes selon le type
        return match($this) {
            self::IDENTITY_CARD,
            self::PASSPORT,
            self::DRIVER_LICENSE,
            self::SELFIE_WITH_ID => 5 * 1024 * 1024, // 5MB

            self::PROOF_OF_INCOME,
            self::BANK_STATEMENT,
            self::PAYSLIP,
            self::TAX_RETURN,
            self::EMPLOYMENT_CONTRACT => 10 * 1024 * 1024, // 10MB
        };
    }

    public function getAllowedMimeTypes(): array
    {
        return match($this->category()) {
            'identity' => ['image/jpeg', 'image/png', 'image/webp'],
            'financial' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
            default => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf']
        };
    }

    public function isRequired(): bool
    {
        // Définit si le document est obligatoire pour la validation du profil
        return match($this) {
            self::IDENTITY_CARD,
            self::PASSPORT,
            self::DRIVER_LICENSE => true, // Au moins un des trois
            self::SELFIE_WITH_ID => true, // Obligatoire avec un document d'identité
            default => false
        };
    }

    public function getExpirationMonths(): ?int
    {
        // Durée de validité en mois
        return match($this) {
            self::BANK_STATEMENT => 3,
            self::PAYSLIP => 3,
            self::TAX_RETURN => 12,
            default => null // Pas d'expiration
        };
    }
}
