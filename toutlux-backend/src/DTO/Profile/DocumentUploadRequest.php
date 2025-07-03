<?php

namespace App\DTO\Profile;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class DocumentUploadRequest
{
    #[Assert\NotBlank(message: 'Le type de document est requis')]
    #[Assert\Choice(
        choices: ['identity', 'financial'],
        message: 'Type de document invalide'
    )]
    public string $type;

    #[Assert\Length(max: 100)]
    public ?string $subType = null;

    #[Assert\NotNull(message: 'Le fichier est requis')]
    #[Assert\File(
        maxSize: '10M',
        mimeTypes: ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
        mimeTypesMessage: 'Veuillez télécharger un fichier valide (PDF, JPEG, PNG ou WebP)'
    )]
    public UploadedFile $document;

    #[Assert\Length(max: 255)]
    public ?string $title = null;

    #[Assert\Length(max: 1000)]
    public ?string $description = null;

    #[Assert\Length(max: 100)]
    public ?string $documentNumber = null;

    #[Assert\Length(max: 100)]
    public ?string $issuingAuthority = null;

    #[Assert\Date]
    public ?string $issueDate = null;

    #[Assert\Date]
    public ?string $expiresAt = null;
}
