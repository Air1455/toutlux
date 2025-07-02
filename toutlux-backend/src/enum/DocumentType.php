<?php

namespace App\Enum;

enum DocumentType: string
{
    // Identity documents
    case IDENTITY_CARD = 'identity_card';
    case PASSPORT = 'passport';
    case DRIVER_LICENSE = 'driver_license';
    case SELFIE_WITH_ID = 'selfie_with_id';

    // Financial documents
    case BANK_STATEMENT = 'bank_statement';
    case PAYSLIP = 'payslip';
    case TAX_RETURN = 'tax_return';
    case PROOF_OF_INCOME = 'proof_of_income';
    case EMPLOYMENT_CONTRACT = 'employment_contract';

    // Other documents
    case PROOF_OF_ADDRESS = 'proof_of_address';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::IDENTITY_CARD => 'Carte d\'identité',
            self::PASSPORT => 'Passeport',
            self::DRIVER_LICENSE => 'Permis de conduire',
            self::SELFIE_WITH_ID => 'Selfie avec pièce d\'identité',
            self::BANK_STATEMENT => 'Relevé bancaire',
            self::PAYSLIP => 'Bulletin de salaire',
            self::TAX_RETURN => 'Avis d\'imposition',
            self::PROOF_OF_INCOME => 'Justificatif de revenus',
            self::EMPLOYMENT_CONTRACT => 'Contrat de travail',
            self::PROOF_OF_ADDRESS => 'Justificatif de domicile',
            self::OTHER => 'Autre document',
        };
    }

    public function category(): string
    {
        return match($this) {
            self::IDENTITY_CARD,
            self::PASSPORT,
            self::DRIVER_LICENSE,
            self::SELFIE_WITH_ID => 'identity',

            self::BANK_STATEMENT,
            self::PAYSLIP,
            self::TAX_RETURN,
            self::PROOF_OF_INCOME,
            self::EMPLOYMENT_CONTRACT => 'financial',

            self::PROOF_OF_ADDRESS,
            self::OTHER => 'other',
        };
    }

    public function getVichMapping(): string
    {
        return match($this->category()) {
            'identity' => 'identity_documents',
            'financial' => 'financial_documents',
            default => 'media_objects',
        };
    }

    public function getMaxFileSize(): string
    {
        return '10M';
    }

    public function getAllowedMimeTypes(): array
    {
        return [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp'
        ];
    }

    public static function getIdentityTypes(): array
    {
        return [
            self::IDENTITY_CARD,
            self::PASSPORT,
            self::DRIVER_LICENSE,
            self::SELFIE_WITH_ID,
        ];
    }

    public static function getFinancialTypes(): array
    {
        return [
            self::BANK_STATEMENT,
            self::PAYSLIP,
            self::TAX_RETURN,
            self::PROOF_OF_INCOME,
            self::EMPLOYMENT_CONTRACT,
        ];
    }
}
