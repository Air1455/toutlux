<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/notifications',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['notification:list']],
            paginationEnabled: true,
            paginationItemsPerPage: 20
        ),
        new Get(
            uriTemplate: '/notifications/{id}',
            security: "is_granted('ROLE_USER') and object.getUser() == user",
            normalizationContext: ['groups' => ['notification:read']]
        ),
        new Put(
            uriTemplate: '/notifications/{id}/read',
            security: "is_granted('ROLE_USER') and object.getUser() == user",
            denormalizationContext: ['groups' => []],
            name: 'notification_mark_read'
        ),
        new Put(
            uriTemplate: '/notifications/read-all',
            security: "is_granted('ROLE_USER')",
            denormalizationContext: ['groups' => []],
            name: 'notification_mark_all_read'
        ),
        new Delete(
            uriTemplate: '/notifications/{id}',
            security: "is_granted('ROLE_USER') and object.getUser() == user"
        )
    ]
)]
#[ApiFilter(BooleanFilter::class, properties: ['isRead'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt'])]
class Notification
{
    // Notification types
    public const TYPE_WELCOME = 'welcome';
    public const TYPE_EMAIL_VERIFICATION = 'email_verification';
    public const TYPE_DOCUMENT_SUBMITTED = 'document_submitted';
    public const TYPE_DOCUMENT_APPROVED = 'document_approved';
    public const TYPE_DOCUMENT_REJECTED = 'document_rejected';
    public const TYPE_MESSAGE_RECEIVED = 'message_received';
    public const TYPE_MESSAGE_MODERATED = 'message_moderated';
    public const TYPE_PROFILE_INCOMPLETE = 'profile_incomplete';
    public const TYPE_PROPERTY_VIEW = 'property_view';
    public const TYPE_TRUST_SCORE_UPDATE = 'trust_score_update';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['notification:list', 'notification:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['notification:list', 'notification:read'])]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    #[Groups(['notification:list', 'notification:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['notification:read'])]
    private ?string $content = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['notification:list', 'notification:read'])]
    private bool $isRead = false;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['notification:read'])]
    private array $data = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['notification:list', 'notification:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
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
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
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

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): static
    {
        $this->data = $data;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    #[Groups(['notification:read'])]
    public function getIcon(): string
    {
        return match($this->type) {
            self::TYPE_WELCOME => 'user-plus',
            self::TYPE_EMAIL_VERIFICATION => 'mail',
            self::TYPE_DOCUMENT_SUBMITTED, self::TYPE_DOCUMENT_APPROVED, self::TYPE_DOCUMENT_REJECTED => 'file-text',
            self::TYPE_MESSAGE_RECEIVED, self::TYPE_MESSAGE_MODERATED => 'message-circle',
            self::TYPE_PROFILE_INCOMPLETE => 'alert-circle',
            self::TYPE_PROPERTY_VIEW => 'eye',
            self::TYPE_TRUST_SCORE_UPDATE => 'star',
            default => 'bell'
        };
    }

    #[Groups(['notification:read'])]
    public function getActionUrl(): ?string
    {
        return match($this->type) {
            self::TYPE_EMAIL_VERIFICATION => '/verify-email',
            self::TYPE_PROFILE_INCOMPLETE => '/profile',
            self::TYPE_MESSAGE_RECEIVED, self::TYPE_MESSAGE_MODERATED => isset($this->data['messageId']) ? '/messages/' . $this->data['messageId'] : '/messages',
            self::TYPE_DOCUMENT_APPROVED, self::TYPE_DOCUMENT_REJECTED => '/profile/documents',
            self::TYPE_PROPERTY_VIEW => isset($this->data['propertyId']) ? '/properties/' . $this->data['propertyId'] : null,
            default => null
        };
    }

    public function markAsRead(): void
    {
        $this->isRead = true;
    }

    // Static factory methods for common notifications
    public static function createWelcomeNotification(User $user): self
    {
        $notification = new self();
        $notification->setUser($user);
        $notification->setType(self::TYPE_WELCOME);
        $notification->setTitle('Welcome to Real Estate App!');
        $notification->setContent('Thank you for joining us. Complete your profile to get started.');

        return $notification;
    }

    public static function createDocumentApprovedNotification(User $user, Document $document): self
    {
        $notification = new self();
        $notification->setUser($user);
        $notification->setType(self::TYPE_DOCUMENT_APPROVED);
        $notification->setTitle('Document Approved');
        $notification->setContent(sprintf('Your %s has been approved.', $document->getTypeLabel()));
        $notification->setData([
            'documentId' => $document->getId()->toRfc4122(),
            'documentType' => $document->getType()
        ]);

        return $notification;
    }

    public static function createMessageReceivedNotification(User $user, Message $message): self
    {
        $notification = new self();
        $notification->setUser($user);
        $notification->setType(self::TYPE_MESSAGE_RECEIVED);
        $notification->setTitle('New Message');
        $notification->setContent(sprintf('You have a new message from %s', $message->getSender()->getFullName() ?? $message->getSender()->getEmail()));
        $notification->setData([
            'messageId' => $message->getId()->toRfc4122(),
            'senderId' => $message->getSender()->getId()->toRfc4122()
        ]);

        return $notification;
    }
}
