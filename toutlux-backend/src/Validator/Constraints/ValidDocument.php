<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class ValidDocument extends Constraint
{
    public string $message = 'Le document "{{ value }}" n\'est pas valide.';
    public string $mimeTypeMessage = 'Le type de fichier "{{ mime_type }}" n\'est pas autorisé pour ce type de document.';
    public string $sizeMessage = 'Le fichier est trop volumineux ({{ size }}). La taille maximale autorisée est {{ max_size }}.';
    public string $expiredMessage = 'Le document ne peut pas avoir une date d\'expiration dans le passé.';

    public ?string $documentType = null;
    public ?int $maxSize = null; // en bytes
    public array $allowedMimeTypes = [];
    public bool $checkExpiration = true;

    public function __construct(
        ?string $documentType = null,
        ?int $maxSize = null,
        array $allowedMimeTypes = [],
        bool $checkExpiration = true,
        ?string $message = null,
        ?string $mimeTypeMessage = null,
        ?string $sizeMessage = null,
        ?string $expiredMessage = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct([], $groups, $payload);

        $this->documentType = $documentType;
        $this->maxSize = $maxSize;
        $this->allowedMimeTypes = $allowedMimeTypes;
        $this->checkExpiration = $checkExpiration;
        $this->message = $message ?? $this->message;
        $this->mimeTypeMessage = $mimeTypeMessage ?? $this->mimeTypeMessage;
        $this->sizeMessage = $sizeMessage ?? $this->sizeMessage;
        $this->expiredMessage = $expiredMessage ?? $this->expiredMessage;
    }
}
