<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use App\Enum\MessageStatus;
use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['message:list']]
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            denormalizationContext: ['groups' => ['message:write']],
            normalizationContext: ['groups' => ['message:read']],
            validationContext: ['groups' => ['Default', 'message:create']]
        ),
        new Get(
            security: "is_granted('ROLE_USER') and (object.getSender() == user or object.getRecipient() == user or is_granted('ROLE_ADMIN'))",
            normalizationContext: ['groups' => ['message:read', 'message:detail']]
        ),
        new Put(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['message:admin']],
            normalizationContext: ['groups' => ['message:read']]
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        )
    ],
    normalizationContext: ['groups' => ['message:read']],
    denormalizationContext: ['groups' => ['message:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 20
)]
#[ApiFilter(SearchFilter::class, properties: ['content' => 'partial'])]
#[ApiFilter(BooleanFilter::class, properties: ['isRead', 'adminValidated'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt'], arguments: ['orderParameterName' => 'order'])]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['message:read', 'message:list'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(groups: ['message:create'])]
    #[Assert\Length(min: 10, minMessage: 'Le message doit contenir au moins 10 caractères')]
    #[Groups(['message:read', 'message:write', 'message:list'])]
    private ?string $content = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['message:read', 'message:write', 'message:list'])]
    private ?string $subject = null;

    #[ORM\ManyToOne(inversedBy: 'sentMessages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['message:read', 'message:list'])]
    private ?User $sender = null;

    #[ORM\ManyToOne(inversedBy: 'receivedMessages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['message:read', 'message:write', 'message:list'])]
    private ?User $recipient = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[Groups(['message:read', 'message:write'])]
    private ?Property $property = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['message:read'])]
    private bool $isRead = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['message:read'])]
    private ?\DateTimeImmutable $readAt = null;

    // Admin validation fields
    #[ORM\Column(type: 'string', enumType: MessageStatus::class)]
    #[Groups(['message:read', 'message:admin'])]
    private MessageStatus $status = MessageStatus::PENDING;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['message:read'])]
    private bool $adminValidated = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['message:read'])]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\ManyToOne]
    #[Groups(['message:read'])]
    private ?User $validatedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['message:read', 'message:admin'])]
    private ?string $adminNote = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['message:read', 'message:admin'])]
    private ?string $originalContent = null;

    // Parent/Reply system
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[Groups(['message:read'])]
    private ?self $parentMessage = null;

    // Timestamps
    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['message:read', 'message:list'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->status = MessageStatus::PENDING;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;
        return $this;
    }

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient): static
    {
        $this->recipient = $recipient;
        return $this;
    }

    public function getProperty(): ?Property
    {
        return $this->property;
    }

    public function setProperty(?Property $property): static
    {
        $this->property = $property;
        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;

        if ($isRead && !$this->readAt) {
            $this->readAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTimeImmutable $readAt): static
    {
        $this->readAt = $readAt;
        return $this;
    }

    public function getStatus(): MessageStatus
    {
        return $this->status;
    }

    public function setStatus(MessageStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function isAdminValidated(): bool
    {
        return $this->adminValidated;
    }

    public function setAdminValidated(bool $adminValidated): static
    {
        $this->adminValidated = $adminValidated;

        if ($adminValidated) {
            $this->validatedAt = new \DateTimeImmutable();
            $this->status = MessageStatus::APPROVED;
        }

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

    public function getAdminNote(): ?string
    {
        return $this->adminNote;
    }

    public function setAdminNote(?string $adminNote): static
    {
        $this->adminNote = $adminNote;
        return $this;
    }

    public function getOriginalContent(): ?string
    {
        return $this->originalContent;
    }

    public function setOriginalContent(?string $originalContent): static
    {
        $this->originalContent = $originalContent;
        return $this;
    }

    public function getParentMessage(): ?self
    {
        return $this->parentMessage;
    }

    public function setParentMessage(?self $parentMessage): static
    {
        $this->parentMessage = $parentMessage;
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
     * Mark message as read
     */
    public function markAsRead(): void
    {
        if (!$this->isRead) {
            $this->isRead = true;
            $this->readAt = new \DateTimeImmutable();
        }
    }

    /**
     * Approve message (by admin)
     */
    public function approve(?User $admin = null): void
    {
        $this->adminValidated = true;
        $this->status = MessageStatus::APPROVED;
        $this->validatedAt = new \DateTimeImmutable();
        $this->validatedBy = $admin;
    }

    /**
     * Reject message (by admin)
     */
    public function reject(?User $admin = null, ?string $reason = null): void
    {
        $this->adminValidated = false;
        $this->status = MessageStatus::REJECTED;
        $this->validatedAt = new \DateTimeImmutable();
        $this->validatedBy = $admin;
        $this->adminNote = $reason;
    }
}
