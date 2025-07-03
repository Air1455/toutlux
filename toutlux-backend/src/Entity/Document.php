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
            security: "is_granted('ROLE_USER')",
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

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['document:read', 'document:write', 'document:list'])]
    private ?string $subType = null;

    // VichUploader properties
    #[Vich\UploadableField(
        mapping: 'identity_documents',
        fileNameProperty: 'fileName',
        size: 'fileSize',
        mimeType: 'mimeType',
        originalName: 'originalName'
    )]
    #[Assert\NotNull(groups: ['document:create'])]
    #[Assert\File(
        maxSize: '10M',
        mimeTypes: ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
        mimeTypesMessage: 'Veuillez télécharger un fichier valide (PDF, JPEG, PNG ou WebP)'
    )]
    private ?File $file = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['document:read', 'document:list', 'user:detail'])]
    private ?string $fileName = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['document:read'])]
    private ?int $fileSize = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filePath = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['document:read'])]
    private ?string $mimeType = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['document:read'])]
    private ?string $originalName = null;

    // Document information
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['document:read', 'document:write', 'document:list'])]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    #[Assert\Length(max: 1000)]
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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['document:read', 'document:admin'])]
    private ?string $validationNotes = null;

    // Document metadata
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    #[Assert\GreaterThan('today')]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    #[Assert\Length(max: 100)]
    private ?string $documentNumber = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    #[Assert\Length(max: 100)]
    private ?string $issuingAuthority = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?\DateTimeImmutable $issueDate = null;

    // Security and integrity
    #[ORM\Column(type: 'boolean')]
    #[Groups(['document:read'])]
    private bool $isEncrypted = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $checksum = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $encryptionKey = null;

    // URL for API access
    #[Groups(['document:read', 'user:detail'])]
    private ?string $fileUrl = null;

    // OCR and metadata extraction
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['document:read', 'document:admin'])]
    private ?array $extractedData = null;

    #[ORM\Column(type: 'boolean')]
    private bool $ocrProcessed = false;

    // Audit trail
    #[ORM\Column(type: Types::JSON)]
    #[Groups(['document:admin'])]
    private array $auditLog = [];

    // Timestamps
    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['document:read', 'document:list'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastAccessedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->status = DocumentStatus::PENDING;
        $this->auditLog = [];
    }

    // Getters and Setters

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
        $this->addAuditEntry('type_changed', ['new_type' => $type->value]);
        return $this;
    }

    public function getSubType(): ?string
    {
        return $this->subType;
    }

    public function setSubType(?string $subType): static
    {
        $this->subType = $subType;
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
            if ($file->isValid()) {
                $this->checksum = hash_file('sha256', $file->getPathname());
            }
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

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): static
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(?string $originalName): static
    {
        $this->originalName = $originalName;
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
        $oldStatus = $this->status;
        $this->status = $status;
        $this->addAuditEntry('status_changed', [
            'old_status' => $oldStatus->value,
            'new_status' => $status->value
        ]);
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

    public function getValidationNotes(): ?string
    {
        return $this->validationNotes;
    }

    public function setValidationNotes(?string $validationNotes): static
    {
        $this->validationNotes = $validationNotes;
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

    public function getEncryptionKey(): ?string
    {
        return $this->encryptionKey;
    }

    public function setEncryptionKey(?string $encryptionKey): static
    {
        $this->encryptionKey = $encryptionKey;
        $this->isEncrypted = $encryptionKey !== null;
        return $this;
    }

    public function getFileUrl(): ?string
    {
        if ($this->fileName) {
            return '/uploads/documents/' . $this->getUploadDir() . '/' . $this->fileName;
        }
        return null;
    }

    public function setFileUrl(?string $fileUrl): static
    {
        $this->fileUrl = $fileUrl;
        return $this;
    }

    public function getExtractedData(): ?array
    {
        return $this->extractedData;
    }

    public function setExtractedData(?array $extractedData): static
    {
        $this->extractedData = $extractedData;
        return $this;
    }

    public function isOcrProcessed(): bool
    {
        return $this->ocrProcessed;
    }

    public function setOcrProcessed(bool $ocrProcessed): static
    {
        $this->ocrProcessed = $ocrProcessed;
        return $this;
    }

    public function getAuditLog(): array
    {
        return $this->auditLog;
    }

    public function setAuditLog(array $auditLog): static
    {
        $this->auditLog = $auditLog;
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

    public function getLastAccessedAt(): ?\DateTimeImmutable
    {
        return $this->lastAccessedAt;
    }

    public function setLastAccessedAt(?\DateTimeImmutable $lastAccessedAt): static
    {
        $this->lastAccessedAt = $lastAccessedAt;
        return $this;
    }

    // Lifecycle callbacks

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->addAuditEntry('document_created');
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->addAuditEntry('document_updated');
    }

    // Business logic methods

    /**
     * Approve document
     */
    public function approve(?User $admin = null, ?string $notes = null): void
    {
        if ($this->status !== DocumentStatus::PENDING) {
            throw new \LogicException('Only pending documents can be approved');
        }

        $this->status = DocumentStatus::APPROVED;
        $this->validatedAt = new \DateTimeImmutable();
        $this->validatedBy = $admin;
        $this->validationNotes = $notes;
        $this->rejectionReason = null;

        $this->addAuditEntry('document_approved', [
            'admin_id' => $admin?->getId(),
            'notes' => $notes
        ]);
    }

    /**
     * Reject document
     */
    public function reject(?User $admin = null, ?string $reason = null, ?string $notes = null): void
    {
        if ($this->status !== DocumentStatus::PENDING) {
            throw new \LogicException('Only pending documents can be rejected');
        }

        $this->status = DocumentStatus::REJECTED;
        $this->validatedAt = new \DateTimeImmutable();
        $this->validatedBy = $admin;
        $this->rejectionReason = $reason ?: 'Document non conforme';
        $this->validationNotes = $notes;

        $this->addAuditEntry('document_rejected', [
            'admin_id' => $admin?->getId(),
            'reason' => $reason,
            'notes' => $notes
        ]);
    }

    /**
     * Mark document as expired
     */
    public function markAsExpired(): void
    {
        $this->status = DocumentStatus::EXPIRED;
        $this->addAuditEntry('document_expired');
    }

    /**
     * Check if document is expired
     */
    public function isExpired(): bool
    {
        if ($this->status === DocumentStatus::EXPIRED) {
            return true;
        }

        if ($this->expiresAt && $this->expiresAt < new \DateTimeImmutable()) {
            return true;
        }

        return false;
    }

    /**
     * Check if document can be deleted
     */
    public function canBeDeleted(): bool
    {
        return $this->status === DocumentStatus::PENDING ||
            $this->status === DocumentStatus::REJECTED;
    }

    /**
     * Get upload directory based on document type
     */
    private function getUploadDir(): string
    {
        return match($this->type?->category()) {
            'identity' => 'identity',
            'financial' => 'financial',
            default => 'other'
        };
    }

    /**
     * Add audit log entry
     */
    private function addAuditEntry(string $action, array $data = []): void
    {
        $this->auditLog[] = [
            'action' => $action,
            'timestamp' => (new \DateTimeImmutable())->format('c'),
            'data' => $data
        ];
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

    /**
     * Get full file path for filesystem operations
     */
    public function getFullFilePath(): string
    {
        if (!$this->filePath) {
            throw new \LogicException('File path not set');
        }

        return sprintf('%s/public/uploads/documents/%s/%s',
            $_ENV['KERNEL_PROJECT_DIR'] ?? getcwd(),
            $this->getUploadDir(),
            $this->fileName
        );
    }

    /**
     * Verify file integrity
     */
    public function verifyIntegrity(): bool
    {
        if (!$this->checksum || !file_exists($this->getFullFilePath())) {
            return false;
        }

        $currentChecksum = hash_file('sha256', $this->getFullFilePath());
        return $currentChecksum === $this->checksum;
    }

    /**
     * Update last accessed timestamp
     */
    public function markAsAccessed(): void
    {
        $this->lastAccessedAt = new \DateTimeImmutable();
    }

    /**
     * Get days until expiration
     */
    public function getDaysUntilExpiration(): ?int
    {
        if (!$this->expiresAt) {
            return null;
        }

        $now = new \DateTimeImmutable();
        if ($this->expiresAt < $now) {
            return 0;
        }

        return $now->diff($this->expiresAt)->days;
    }

    /**
     * Check if document needs renewal
     */
    public function needsRenewal(int $daysThreshold = 30): bool
    {
        $daysUntilExpiration = $this->getDaysUntilExpiration();

        return $daysUntilExpiration !== null && $daysUntilExpiration <= $daysThreshold;
    }
}
