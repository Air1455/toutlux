<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['notification:read']]
        ),
        new Patch(
            security: "object.getUser() == user",
            denormalizationContext: ['groups' => ['notification:write']]
        )
    ]
)]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['notification:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['notification:read'])]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['notification:read'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 1000)]
    private ?string $text = null;

    #[ORM\Column(length: 50)]
    #[Groups(['notification:read'])]
    #[Assert\Choice(choices: [
        'new_message', 'property_alert', 'price_change', 'appointment_reminder',
        'listing_update', 'security_alert', 'verification_code', 'system_update',
        'payment_reminder', 'listing_approved', 'listing_rejected', 'profile_update'
    ])]
    private ?string $type = null;

    #[ORM\Column(length: 20)]
    #[Groups(['notification:read'])]
    #[Assert\Choice(choices: ['low', 'medium', 'high', 'urgent'])]
    private ?string $priority = 'medium';

    #[ORM\Column]
    #[Groups(['notification:read', 'notification:write'])]
    private bool $isRead = false;

    #[ORM\Column]
    #[Groups(['notification:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['notification:read'])]
    private ?\DateTimeImmutable $readAt = null;

    // Données additionnelles en JSON pour les notifications complexes
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['notification:read'])]
    private ?array $data = null;

    // Lien vers l'objet concerné (propriété, message, etc.)
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['notification:read'])]
    private ?string $relatedEntityType = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['notification:read'])]
    private ?int $relatedEntityId = null;

    // URL d'action (deep link dans l'app)
    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['notification:read'])]
    private ?string $actionUrl = null;

    // Icône pour l'affichage
    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['notification:read'])]
    private ?string $icon = null;

    // Pour les notifications programmées
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $scheduledFor = null;

    #[ORM\Column]
    private bool $isSent = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isRead = false;
        $this->isSent = false;
        $this->priority = 'medium';
    }

    #[ORM\PreUpdate]
    public function updateReadStatus(): void
    {
        if ($this->isRead && !$this->readAt) {
            $this->readAt = new \DateTimeImmutable();
        }
    }

    // === MÉTHODES UTILITAIRES ===

    public function markAsRead(): void
    {
        $this->isRead = true;
        $this->readAt = new \DateTimeImmutable();
    }

    public function markAsUnread(): void
    {
        $this->isRead = false;
        $this->readAt = null;
    }

    public function markAsSent(): void
    {
        $this->isSent = true;
    }

    public function isUrgent(): bool
    {
        return $this->priority === 'urgent';
    }

    public function isScheduled(): bool
    {
        return $this->scheduledFor !== null && $this->scheduledFor > new \DateTimeImmutable();
    }

    public function shouldBeSent(): bool
    {
        if ($this->isSent) {
            return false;
        }

        if ($this->scheduledFor) {
            return $this->scheduledFor <= new \DateTimeImmutable();
        }

        return true;
    }

    public function getTimeSinceCreated(): string
    {
        $now = new \DateTimeImmutable();
        $diff = $now->diff($this->createdAt);

        if ($diff->days > 0) {
            return $diff->days . ' jour' . ($diff->days > 1 ? 's' : '');
        } elseif ($diff->h > 0) {
            return $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
        } else {
            return 'À l\'instant';
        }
    }

    // === MÉTHODES STATIQUES POUR CRÉER DES NOTIFICATIONS ===

    public static function createNewMessage(User $user, string $senderName, string $messagePreview): self
    {
        $notification = new self();
        $notification->setUser($user);
        $notification->setType('new_message');
        $notification->setText("Nouveau message de {$senderName}: {$messagePreview}");
        $notification->setPriority('medium');
        $notification->setIcon('message');

        return $notification;
    }

    public static function createPropertyAlert(User $user, string $propertyTitle, string $location): self
    {
        $notification = new self();
        $notification->setUser($user);
        $notification->setType('property_alert');
        $notification->setText("Nouvelle propriété correspondant à vos critères: {$propertyTitle} à {$location}");
        $notification->setPriority('medium');
        $notification->setIcon('home');

        return $notification;
    }

    public static function createVerificationCode(User $user, string $code, string $method): self
    {
        $notification = new self();
        $notification->setUser($user);
        $notification->setType('verification_code');
        $notification->setText("Votre code de vérification {$method}: {$code}");
        $notification->setPriority('high');
        $notification->setIcon('shield-check');

        return $notification;
    }

    public static function createSecurityAlert(User $user, string $action, string $location): self
    {
        $notification = new self();
        $notification->setUser($user);
        $notification->setType('security_alert');
        $notification->setText("Activité de sécurité: {$action} depuis {$location}");
        $notification->setPriority('urgent');
        $notification->setIcon('alert');

        return $notification;
    }

    // === GETTERS/SETTERS ===

    public function getId(): ?int
    {
        return $this->id;
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

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;
        return $this;
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

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function getRelatedEntityType(): ?string
    {
        return $this->relatedEntityType;
    }

    public function setRelatedEntityType(?string $relatedEntityType): static
    {
        $this->relatedEntityType = $relatedEntityType;
        return $this;
    }

    public function getRelatedEntityId(): ?int
    {
        return $this->relatedEntityId;
    }

    public function setRelatedEntityId(?int $relatedEntityId): static
    {
        $this->relatedEntityId = $relatedEntityId;
        return $this;
    }

    public function getActionUrl(): ?string
    {
        return $this->actionUrl;
    }

    public function setActionUrl(?string $actionUrl): static
    {
        $this->actionUrl = $actionUrl;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function getScheduledFor(): ?\DateTimeImmutable
    {
        return $this->scheduledFor;
    }

    public function setScheduledFor(?\DateTimeImmutable $scheduledFor): static
    {
        $this->scheduledFor = $scheduledFor;
        return $this;
    }

    public function isSent(): bool
    {
        return $this->isSent;
    }

    public function setIsSent(bool $isSent): static
    {
        $this->isSent = $isSent;
        return $this;
    }
}
