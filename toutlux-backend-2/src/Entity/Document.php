<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/profile/documents',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['document:list']]
        ),
        new Get(
            uriTemplate: '/profile/documents/{id}',
            security: "is_granted('ROLE_USER') and object.getUser() == user",
            normalizationContext: ['groups' => ['document:read']]
        ),
        new Post(
            uriTemplate: '/profile/documents',
            inputFormats: ['multipart' => ['multipart/form-data']],
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['document:read']],
            denormalizationContext: ['groups' => ['document:create']],
            validationContext: ['groups' => ['Default', 'document:create']]
        ),
        new Delete(
            uriTemplate: '/profile/documents/{id}',
            security: "is_granted('ROLE_USER') and object.getUser() == user and object.getStatus() == 'pending'"
        )
    ]
)]
class Document
{
    public const TYPE_IDENTITY = 'identity';
    public const TYPE_SELFIE = 'selfie';
    public const TYPE_FINANCIAL = 'financial';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['document:list', 'document:read', 'user:detail'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Document type is required')]
    #[Assert\Choice(
        choices: [self::TYPE_IDENTITY, self::TYPE_SELFIE, self::TYPE_FINANCIAL],
        message: 'Invalid document type'
    )]
    #[Groups(['document:list', 'document:read', 'document:create'])]
    private ?string $type = null;

    #[Vich\UploadableField(
        mapping: 'identity_documents',
        fileNameProperty: 'fileName',
        size: 'fileSize'
    )]
    #[Assert\NotNull(message: 'Document file is required', groups: ['document:create'])]
    #[Assert\File(
        maxSize: '10M',
        mimeTypes: [
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/pdf'
        ],
        mimeTypesMessage: 'Please upload a valid document (JPEG, PNG, WebP, or PDF)'
    )]
    #[Groups(['document:create'])]
    private ?File $file = null;

    #[ORM\Column(length: 255)]
    private ?string $fileName = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['document:read'])]
    private ?int $fileSize = null;

    #[ORM\Column(length: 20)]
    #[Groups(['document:list', 'document:read', 'user:detail'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['document:read'])]
    private ?string $rejectionReason = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['document:read'])]
    private ?\DateTimeInterface $validatedAt = null;

    #[ORM\ManyToOne]
    private ?User $validatedBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['document:list', 'document:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        // Update Vich mapping based on type
        if ($type === self::TYPE_FINANCIAL) {
            // This will be handled by a custom namer
        }

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file = null): static
    {
        $this->file = $file;

        if (null !== $file) {
            $this->updatedAt = new \DateTime();
        }

        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): static
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): static
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    #[Groups(['document:read'])]
    public function getFileUrl(): ?string
    {
        if ($this->fileName) {
            $prefix = $this->type === self::TYPE_FINANCIAL ? 'financial' : 'identity';
            return '/uploads/documents/' . $prefix . '/' . $this->fileName;
        }
        return null;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): static
    {
        $this->rejectionReason = $rejectionReason;
        return $this;
    }

    public function getValidatedAt(): ?\DateTimeInterface
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTimeInterface $validatedAt): static
    {
        $this->validatedAt = $validatedAt;
        return $this;
    }

    public function getValidatedBy(): ?User
    {
        return $this->validatedBy;
    }

    public function setValidatedBy(?User $validatedBy): static
    {
        $this->validatedBy = $validatedBy;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function approve(User $validator): void
    {
        $this->status = self::STATUS_APPROVED;
        $this->validatedBy = $validator;
        $this->validatedAt = new \DateTime();
        $this->rejectionReason = null;
    }

    public function reject(User $validator, string $reason): void
    {
        $this->status = self::STATUS_REJECTED;
        $this->validatedBy = $validator;
        $this->validatedAt = new \DateTime();
        $this->rejectionReason = $reason;
    }

    #[Groups(['document:read'])]
    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_IDENTITY => 'Identity Document',
            self::TYPE_SELFIE => 'Selfie with ID',
            self::TYPE_FINANCIAL => 'Financial Document',
            default => 'Unknown'
        };
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
