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
    case TAX_RETURN = 'tax_return';
    case EMPLOYMENT_CONTRACT = 'employment_contract';

    public function getLabel(): string
    {
        return match($this) {
            self::IDENTITY_CARD => 'Carte d\'identité',
            self::PASSPORT => 'Passeport',
            self::DRIVER_LICENSE => 'Permis de conduire',
            self::SELFIE_WITH_ID => 'Selfie avec pièce d\'identité',
            self::PROOF_OF_INCOME => 'Justificatif de revenus',
            self::BANK_STATEMENT => 'Relevé bancaire',
            self::TAX_RETURN => 'Déclaration d\'impôts',
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
}
