<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Controller\Api\ProfileController;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use App\Validator\Constraints\UniqueEmail;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
#[ApiResource(
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    operations: [
        new GetCollection(
            security: 'is_granted("ROLE_ADMIN")'
        ),
        new Post(
            uriTemplate: '/auth/register',
            controller: ProfileController::class,
            name: 'api_register'
        ),
        new Get(
            security: 'is_granted("ROLE_USER") and object == user'
        ),
        new Put(
            security: 'is_granted("ROLE_USER") and object == user',
            denormalizationContext: ['groups' => ['user:update']]
        ),
        new Patch(
            security: 'is_granted("ROLE_USER") and object == user',
            denormalizationContext: ['groups' => ['user:update']]
        ),
        new Delete(
            security: 'is_granted("ROLE_ADMIN")'
        )
    ],
    order: ['createdAt' => 'DESC']
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'property:read', 'message:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'L\'email doit être valide')]
    #[UniqueEmail]
    #[Groups(['user:read', 'user:write', 'property:read', 'message:read'])]
    private ?string $email = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['user:read'])]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['user:read', 'user:write', 'user:update', 'property:read', 'message:read'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['user:read', 'user:write', 'user:update', 'property:read', 'message:read'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(
        pattern: '/^[+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,5}[-\s\.]?[0-9]{1,5}$/',
        message: 'Le numéro de téléphone n\'est pas valide'
    )]
    #[Groups(['user:read', 'user:write', 'user:update'])]
    private ?string $phoneNumber = null;

    #[Vich\UploadableField(mapping: 'user_avatar', fileNameProperty: 'avatarName')]
    #[Assert\Image(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
        maxSizeMessage: 'L\'image ne doit pas dépasser 5MB',
        mimeTypesMessage: 'Le format de l\'image doit être JPEG, PNG ou WebP'
    )]
    private ?File $avatarFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $avatarName = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 2, scale: 1, nullable: true)]
    #[Groups(['user:read', 'property:read'])]
    private ?string $trustScore = '0.0';

    #[ORM\Column]
    #[Groups(['user:read'])]
    private bool $isVerified = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTimeInterface $emailVerifiedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user:read'])]
    private ?bool $profilePersonalCompleted = false;

    #[ORM\Column(nullable: true)]
    #[Groups(['user:read'])]
    private ?bool $profileIdentityCompleted = false;

    #[ORM\Column(nullable: true)]
    #[Groups(['user:read'])]
    private ?bool $profileFinancialCompleted = false;

    #[ORM\Column(nullable: true)]
    #[Groups(['user:read'])]
    private ?bool $profileTermsAccepted = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $googleData = null;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['user:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\OneToMany(targetEntity: Property::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $properties;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'sender', orphanRemoval: true)]
    private Collection $sentMessages;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'recipient')]
    private Collection $receivedMessages;

    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'user', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $documents;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user', orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $notifications;

    public function __construct()
    {
        $this->roles = [UserRole::USER->value];
        $this->properties = new ArrayCollection();
        $this->sentMessages = new ArrayCollection();
        $this->receivedMessages = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = UserRole::USER->value;
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    #[Groups(['user:read', 'property:read', 'message:read'])]
    public function getFullName(): string
    {
        return trim(sprintf('%s %s', $this->firstName, $this->lastName)) ?: 'Utilisateur';
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getAvatarFile(): ?File
    {
        return $this->avatarFile;
    }

    public function setAvatarFile(?File $avatarFile): static
    {
        $this->avatarFile = $avatarFile;

        if ($avatarFile) {
            $this->updatedAt = new \DateTime();
        }

        return $this;
    }

    public function getAvatarName(): ?string
    {
        return $this->avatarName;
    }

    public function setAvatarName(?string $avatarName): static
    {
        $this->avatarName = $avatarName;
        return $this;
    }

    #[Groups(['user:read', 'property:read'])]
    public function getAvatarUrl(): ?string
    {
        return $this->avatarName ? '/uploads/avatars/' . $this->avatarName : null;
    }

    public function getTrustScore(): ?string
    {
        return $this->trustScore;
    }

    public function setTrustScore(?string $trustScore): static
    {
        $this->trustScore = $trustScore;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getEmailVerifiedAt(): ?\DateTimeInterface
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?\DateTimeInterface $emailVerifiedAt): static
    {
        $this->emailVerifiedAt = $emailVerifiedAt;
        $this->isVerified = $emailVerifiedAt !== null;
        return $this;
    }

    public function isProfilePersonalCompleted(): ?bool
    {
        return $this->profilePersonalCompleted;
    }

    public function setProfilePersonalCompleted(?bool $profilePersonalCompleted): static
    {
        $this->profilePersonalCompleted = $profilePersonalCompleted;
        return $this;
    }

    public function isProfileIdentityCompleted(): ?bool
    {
        return $this->profileIdentityCompleted;
    }

    public function setProfileIdentityCompleted(?bool $profileIdentityCompleted): static
    {
        $this->profileIdentityCompleted = $profileIdentityCompleted;
        return $this;
    }

    public function isProfileFinancialCompleted(): ?bool
    {
        return $this->profileFinancialCompleted;
    }

    public function setProfileFinancialCompleted(?bool $profileFinancialCompleted): static
    {
        $this->profileFinancialCompleted = $profileFinancialCompleted;
        return $this;
    }

    public function isProfileTermsAccepted(): ?bool
    {
        return $this->profileTermsAccepted;
    }

    public function setProfileTermsAccepted(?bool $profileTermsAccepted): static
    {
        $this->profileTermsAccepted = $profileTermsAccepted;
        return $this;
    }

    #[Groups(['user:read'])]
    public function getProfileCompletionPercentage(): int
    {
        $completed = 0;

        if ($this->profilePersonalCompleted) $completed++;
        if ($this->profileIdentityCompleted) $completed++;
        if ($this->profileFinancialCompleted) $completed++;
        if ($this->profileTermsAccepted) $completed++;

        return (int) (($completed / 4) * 100);
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;
        return $this;
    }

    public function getGoogleData(): ?array
    {
        return $this->googleData;
    }

    public function setGoogleData(?array $googleData): static
    {
        $this->googleData = $googleData;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
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

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    /**
     * @return Collection<int, Property>
     */
    public function getProperties(): Collection
    {
        return $this->properties;
    }

    public function addProperty(Property $property): static
    {
        if (!$this->properties->contains($property)) {
            $this->properties->add($property);
            $property->setOwner($this);
        }
        return $this;
    }

    public function removeProperty(Property $property): static
    {
        if ($this->properties->removeElement($property)) {
            if ($property->getOwner() === $this) {
                $property->setOwner(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getSentMessages(): Collection
    {
        return $this->sentMessages;
    }

    public function addSentMessage(Message $sentMessage): static
    {
        if (!$this->sentMessages->contains($sentMessage)) {
            $this->sentMessages->add($sentMessage);
            $sentMessage->setSender($this);
        }
        return $this;
    }

    public function removeSentMessage(Message $sentMessage): static
    {
        if ($this->sentMessages->removeElement($sentMessage)) {
            if ($sentMessage->getSender() === $this) {
                $sentMessage->setSender(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getReceivedMessages(): Collection
    {
        return $this->receivedMessages;
    }

    public function addReceivedMessage(Message $receivedMessage): static
    {
        if (!$this->receivedMessages->contains($receivedMessage)) {
            $this->receivedMessages->add($receivedMessage);
            $receivedMessage->setRecipient($this);
        }
        return $this;
    }

    public function removeReceivedMessage(Message $receivedMessage): static
    {
        if ($this->receivedMessages->removeElement($receivedMessage)) {
            if ($receivedMessage->getRecipient() === $this) {
                $receivedMessage->setRecipient(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setUser($this);
        }
        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getUser() === $this) {
                $document->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }
        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }
        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::ADMIN->value);
    }
}
