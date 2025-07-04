<?php

namespace App\Validator\Constraints;

use App\Entity\Document;
use App\Enum\DocumentType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidDocumentValidator extends ConstraintValidator
{
    private const DEFAULT_MAX_SIZE = 10 * 1024 * 1024; // 10MB

    private const ALLOWED_MIME_TYPES = [
        'identity' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
        'financial' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
        'default' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf']
    ];

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidDocument) {
            throw new UnexpectedTypeException($constraint, ValidDocument::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        // Si c'est un Document entity
        if ($value instanceof Document) {
            $this->validateDocument($value, $constraint);
            return;
        }

        // Si c'est un fichier uploadé
        if ($value instanceof UploadedFile || $value instanceof File) {
            $this->validateFile($value, $constraint);
            return;
        }

        throw new UnexpectedValueException($value, 'Document entity or UploadedFile');
    }

    private function validateDocument(Document $document, ValidDocument $constraint): void
    {
        // Vérifier l'expiration
        if ($constraint->checkExpiration && $document->isExpired()) {
            $this->context->buildViolation($constraint->expiredMessage)
                ->addViolation();
        }

        // Vérifier le type MIME si un fichier est associé
        if ($document->getMimeType() && $constraint->documentType) {
            $allowedMimeTypes = $constraint->allowedMimeTypes ?: $this->getAllowedMimeTypes($constraint->documentType);

            if (!in_array($document->getMimeType(), $allowedMimeTypes)) {
                $this->context->buildViolation($constraint->mimeTypeMessage)
                    ->setParameter('{{ mime_type }}', $document->getMimeType())
                    ->addViolation();
            }
        }

        // Vérifier la taille
        if ($document->getFileSize() && $constraint->maxSize) {
            if ($document->getFileSize() > $constraint->maxSize) {
                $this->context->buildViolation($constraint->sizeMessage)
                    ->setParameter('{{ size }}', $this->formatBytes($document->getFileSize()))
                    ->setParameter('{{ max_size }}', $this->formatBytes($constraint->maxSize))
                    ->addViolation();
            }
        }
    }

    private function validateFile(File $file, ValidDocument $constraint): void
    {
        // Vérifier si le fichier est valide
        if (!$file->isValid()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $file->getClientOriginalName())
                ->addViolation();
            return;
        }

        // Vérifier le type MIME
        if ($constraint->documentType || !empty($constraint->allowedMimeTypes)) {
            $mimeType = $file->getMimeType();
            $allowedMimeTypes = $constraint->allowedMimeTypes ?: $this->getAllowedMimeTypes($constraint->documentType);

            if (!in_array($mimeType, $allowedMimeTypes)) {
                $this->context->buildViolation($constraint->mimeTypeMessage)
                    ->setParameter('{{ mime_type }}', $mimeType)
                    ->addViolation();
            }
        }

        // Vérifier la taille
        $maxSize = $constraint->maxSize ?: self::DEFAULT_MAX_SIZE;
        if ($file->getSize() > $maxSize) {
            $this->context->buildViolation($constraint->sizeMessage)
                ->setParameter('{{ size }}', $this->formatBytes($file->getSize()))
                ->setParameter('{{ max_size }}', $this->formatBytes($maxSize))
                ->addViolation();
        }
    }

    private function getAllowedMimeTypes(?string $documentType): array
    {
        if (!$documentType) {
            return self::ALLOWED_MIME_TYPES['default'];
        }

        // Si c'est un enum DocumentType
        if (enum_exists(DocumentType::class)) {
            $category = match($documentType) {
                'identity_card', 'passport', 'driver_license', 'selfie_with_id' => 'identity',
                'bank_statement', 'payslip', 'tax_return', 'proof_of_income', 'employment_contract' => 'financial',
                default => 'default'
            };

            return self::ALLOWED_MIME_TYPES[$category] ?? self::ALLOWED_MIME_TYPES['default'];
        }

        return self::ALLOWED_MIME_TYPES[$documentType] ?? self::ALLOWED_MIME_TYPES['default'];
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
