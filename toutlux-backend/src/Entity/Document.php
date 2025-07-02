<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Enum\DocumentStatus;
use App\Enum\DocumentType;
use App\Repository\DocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_USER') and user == request.get('user')",
            normalizationContext: ['groups' => ['document:list']]
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            denormalizationContext: ['groups' => ['document:write']],
            normalizationContext: ['groups' => ['document:read']],
            inputFormats: ['multipart' => ['multipart/form-data']],
            validationContext: ['groups' => ['Default', 'document:create']]
        ),
        new Get(
            security: "is_granted('ROLE_USER') and (object.getUser() == user or is_granted('ROLE_ADMIN'))",
            normalizationContext: ['groups' => ['document:read', 'document:detail']]
        ),
        new Put(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['document:admin']],
            normalizationContext: ['groups' => ['document:read']]
        ),
        new Delete(
            security: "is_granted('ROLE_USER') and object.getUser() == user and object.getStatus() == 'pending'"
        )
    ],
    normalizationContext: ['groups' => ['document:read']],
    denormalizationContext: ['groups' => ['document:write']]
)]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['document:read', 'document:list', 'user:detail'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', enumType: DocumentType::class)]
    #[Assert\NotNull(groups: ['document:create'])]
    #[Groups(['document:read', 'document:write', 'document:list', 'user:detail'])]
    private ?DocumentType $type = null;

    #[Vich\UploadableField(mapping: 'identity_documents', fileNameProperty: 'fileName', size: 'fileSize')]
    #[Assert\NotNull(groups: ['document:create'])]
    #[Assert\File(
        maxSize: '10M',
        mimeTypes: ['application/pdf', 'image/jpeg', 'image/png'],
        mimeTypesMessage: 'Veuillez télécharger un fichier valide (PDF, JPEG ou PNG)'
    )]
    private ?File $file = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['document:read', 'document:list', 'user:detail'])]
    private ?string $fileName = null;

    #[ORM\Column(nullable: true)]
    private ?int $fileSize = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['document:read', 'document:write', 'document:list'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $description = null;

    #[ORM\Column(type: 'string', enumType: DocumentStatus::class)]
    #[Groups(['document:read', 'document:list', 'user:detail'])]
    private DocumentStatus $status = DocumentStatus::PENDING;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['document:read'])]
    private ?User $user = null;

    // Validation fields
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['document:read'])]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\ManyToOne]
    #[Groups(['document:read'])]
    private ?User $validatedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['document:read', 'document:admin'])]
    private ?string $rejectionReason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['document:read', 'document:admin'])]
    private ?string $adminNote = null;

    // Document metadata
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['document:read'])]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $documentNumber = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $issuingAuthority = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?\DateTimeImmutable $issueDate = null;

    // Security
    #[ORM\Column(type: 'boolean')]
    private bool $isEncrypted = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $checksum = null;

    #[Groups(['document:read', 'user:detail'])]
    private ?string $fileUrl = null;

    // Timestamps
    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['document:read', 'document:list'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->status = DocumentStatus::PENDING;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?DocumentType
    {
        return $this->type;
    }

    public function setType(DocumentType $type): static
    {
        $this->type = $type;

        // Update VichUploader mapping based on type
        $this->updateFileMapping();

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file = null): void
    {
        $this->file = $file;

        if (null !== $file) {
            $this->updatedAt = new \DateTimeImmutable();

            // Calculate checksum
            $this->checksum = hash_file('sha256', $file->getPathname());
        }
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStatus(): DocumentStatus
    {
        return $this->status;
    }

    public function setStatus(DocumentStatus $status): static
    {
        $this->status = $status;
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

    public function getValidatedAt(): ?\DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTimeImmutable $validatedAt): static
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

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): static
    {
        $this->rejectionReason = $rejectionReason;
        return $this;
    }

    public function getAdminNote(): ?string
    {
        return $this->adminNote;
    }

    public function setAdminNote(?string $adminNote): static
    {
        $this->adminNote = $adminNote;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getDocumentNumber(): ?string
    {
        return $this->documentNumber;
    }

    public function setDocumentNumber(?string $documentNumber): static
    {
        $this->documentNumber = $documentNumber;
        return $this;
    }

    public function getIssuingAuthority(): ?string
    {
        return $this->issuingAuthority;
    }

    public function setIssuingAuthority(?string $issuingAuthority): static
    {
        $this->issuingAuthority = $issuingAuthority;
        return $this;
    }

    public function getIssueDate(): ?\DateTimeImmutable
    {
        return $this->issueDate;
    }

    public function setIssueDate(?\DateTimeImmutable $issueDate): static
    {
        $this->issueDate = $issueDate;
        return $this;
    }

    public function isEncrypted(): bool
    {
        return $this->isEncrypted;
    }

    public function setIsEncrypted(bool $isEncrypted): static
    {
        $this->isEncrypted = $isEncrypted;
        return $this;
    }

    public function getChecksum(): ?string
    {
        return $this->checksum;
    }

    public function setChecksum(?string $checksum): static
    {
        $this->checksum = $checksum;
        return $this;
    }

    public function getFileUrl(): ?string
    {
        return $this->fileUrl;
    }

    public function setFileUrl(?string $fileUrl): static
    {
        $this->fileUrl = $fileUrl;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Approve document
     */
    public function approve(?User $admin = null): void
    {
        $this->status = DocumentStatus::APPROVED;
        $this->validatedAt = new \DateTimeImmutable();
        $this->validatedBy = $admin;
        $this->rejectionReason = null;
    }

    /**
     * Reject document
     */
    public function reject(?User $admin = null, ?string $reason = null): void
    {
        $this->status = DocumentStatus::REJECTED;
        $this->validatedAt = new \DateTimeImmutable();
        $this->validatedBy = $admin;
        $this->rejectionReason = $reason;
    }

    /**
     * Check if document is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expiresAt) {
            return false;
        }

        return $this->expiresAt < new \DateTimeImmutable();
    }

    /**
     * Get VichUploader mapping based on document type
     */
    private function updateFileMapping(): void
    {
        // This method would be used to dynamically change the mapping
        // based on the document type if needed
    }

    /**
     * Get human-readable file size
     */
    public function getFormattedFileSize(): string
    {
        $bytes = $this->fileSize ?? 0;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
