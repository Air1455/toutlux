<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
#[Vich\Uploadable]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['user:list', 'user:read']]
        ),
        new Post(
            uriTemplate: '/users/register',
            security: "is_granted('PUBLIC_ACCESS')",
            denormalizationContext: ['groups' => ['user:register']],
            normalizationContext: ['groups' => ['user:read']],
            validationContext: ['groups' => ['Default', 'user:register']]
        ),
        new Get(
            security: "is_granted('ROLE_USER') and object == user or is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['user:read', 'user:detail']]
        ),
        new Put(
            security: "is_granted('ROLE_USER') and object == user",
            denormalizationContext: ['groups' => ['user:update']],
            normalizationContext: ['groups' => ['user:read']]
        ),
        new Patch(
            security: "is_granted('ROLE_USER') and object == user",
            denormalizationContext: ['groups' => ['user:update']],
            normalizationContext: ['groups' => ['user:read']]
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Get(
            uriTemplate: '/users/{id}/profile',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['user:profile']]
        )
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    paginationEnabled: true
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'property:read', 'message:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(groups: ['user:register'])]
    #[Assert\Email(groups: ['user:register', 'user:update'])]
    #[Groups(['user:read', 'user:write', 'user:register', 'property:detail', 'user:list'])]
    private ?string $email = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[Assert\NotBlank(groups: ['user:register'])]
    #[Assert\Length(min: 8, groups: ['user:register'], minMessage: 'Le mot de passe doit contenir au moins 8 caractères')]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
        message: 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre',
        groups: ['user:register']
    )]
    #[Groups(['user:write', 'user:register'])]
    private ?string $plainPassword = null;

    // Personal information
    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['user:read', 'user:write', 'user:profile', 'property:detail', 'user:list'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['user:read', 'user:write', 'user:profile', 'property:detail', 'user:list'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(pattern: '/^[0-9\+\-\.\(\)\s]+$/', message: 'Numéro de téléphone invalide')]
    #[Groups(['user:read', 'user:write', 'user:profile'])]
    private ?string $phoneNumber = null;

    // Avatar
    #[Vich\UploadableField(mapping: 'user_avatars', fileNameProperty: 'avatarName', size: 'avatarSize')]
    #[Assert\Image(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
        mimeTypesMessage: 'Veuillez télécharger une image valide (JPEG, PNG ou WebP)'
    )]
    private ?File $avatarFile = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user:read', 'user:profile'])]
    private ?string $avatarName = null;

    #[ORM\Column(nullable: true)]
    private ?int $avatarSize = null;

    #[Groups(['user:read', 'user:profile', 'property:detail'])]
    private ?string $avatarUrl = null;

    // Trust score
    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, nullable: true)]
    #[Groups(['user:read', 'user:profile', 'property:detail'])]
    private ?string $trustScore = '0.00';

    // Validation status
    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['user:read'])]
    private bool $emailVerified = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['user:read', 'user:profile'])]
    private bool $profileCompleted = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['user:read', 'user:profile'])]
    private bool $identityVerified = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['user:read', 'user:profile'])]
    private bool $financialVerified = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['user:read', 'user:profile'])]
    private bool $termsAccepted = false;

    // Google Auth
    #[ORM\Column(nullable: true)]
    private ?string $googleId = null;

    // Email verification
    #[ORM\Column(nullable: true)]
    private ?string $emailVerificationToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $emailVerificationTokenExpiresAt = null;

    // Relations
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Property::class, cascade: ['persist'])]
    #[Groups(['user:detail'])]
    private Collection $properties;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Document::class, cascade: ['persist', 'remove'])]
    #[Groups(['user:detail'])]
    private Collection $documents;

    #[ORM\OneToMany(mappedBy: 'sender', targetEntity: Message::class)]
    private Collection $sentMessages;

    #[ORM\OneToMany(mappedBy: 'recipient', targetEntity: Message::class)]
    private Collection $receivedMessages;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Notification::class, cascade: ['remove'])]
    private Collection $notifications;

    // Timestamps
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->roles = ['ROLE_USER'];
        $this->properties = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->sentMessages = new ArrayCollection();
        $this->receivedMessages = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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

    /**
     * A visual identifier that represents this user.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
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

    public function getFullName(): string
    {
        return trim(sprintf('%s %s', $this->firstName ?? '', $this->lastName ?? ''));
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

    public function setAvatarFile(?File $avatarFile = null): void
    {
        $this->avatarFile = $avatarFile;

        if (null !== $avatarFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
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

    public function getAvatarSize(): ?int
    {
        return $this->avatarSize;
    }

    public function setAvatarSize(?int $avatarSize): static
    {
        $this->avatarSize = $avatarSize;
        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): static
    {
        $this->avatarUrl = $avatarUrl;
        return $this;
    }

    public function getTrustScore(): ?string
    {
        return $this->trustScore;
    }

    public function setTrustScore(string $trustScore): static
    {
        $this->trustScore = $trustScore;
        return $this;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function setEmailVerified(bool $emailVerified): static
    {
        $this->emailVerified = $emailVerified;
        return $this;
    }

    public function isProfileCompleted(): bool
    {
        return $this->profileCompleted;
    }

    public function setProfileCompleted(bool $profileCompleted): static
    {
        $this->profileCompleted = $profileCompleted;
        return $this;
    }

    public function isIdentityVerified(): bool
    {
        return $this->identityVerified;
    }

    public function setIdentityVerified(bool $identityVerified): static
    {
        $this->identityVerified = $identityVerified;
        return $this;
    }

    public function isFinancialVerified(): bool
    {
        return $this->financialVerified;
    }

    public function setFinancialVerified(bool $financialVerified): static
    {
        $this->financialVerified = $financialVerified;
        return $this;
    }

    public function isTermsAccepted(): bool
    {
        return $this->termsAccepted;
    }

    public function setTermsAccepted(bool $termsAccepted): static
    {
        $this->termsAccepted = $termsAccepted;
        return $this;
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

    public function getEmailVerificationToken(): ?string
    {
        return $this->emailVerificationToken;
    }

    public function setEmailVerificationToken(?string $emailVerificationToken): static
    {
        $this->emailVerificationToken = $emailVerificationToken;
        return $this;
    }

    public function getEmailVerificationTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->emailVerificationTokenExpiresAt;
    }

    public function setEmailVerificationTokenExpiresAt(?\DateTimeImmutable $emailVerificationTokenExpiresAt): static
    {
        $this->emailVerificationTokenExpiresAt = $emailVerificationTokenExpiresAt;
        return $this;
    }

    public function isEmailVerificationTokenExpired(): bool
    {
        return $this->emailVerificationTokenExpiresAt && $this->emailVerificationTokenExpiresAt < new \DateTimeImmutable();
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
            // set the owning side to null (unless already changed)
            if ($property->getOwner() === $this) {
                $property->setOwner(null);
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
            // set the owning side to null (unless already changed)
            if ($document->getUser() === $this) {
                $document->setUser(null);
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

    /**
     * @return Collection<int, Message>
     */
    public function getReceivedMessages(): Collection
    {
        return $this->receivedMessages;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
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
     * Calculate trust score based on verification status
     */
    public function calculateTrustScore(): void
    {
        $score = 0.0;

        if ($this->emailVerified) {
            $score += 1.0;
        }

        if ($this->profileCompleted) {
            $score += 1.0;
        }

        if ($this->identityVerified) {
            $score += 1.5;
        }

        if ($this->financialVerified) {
            $score += 1.5;
        }

        $this->trustScore = number_format($score, 2);
    }

    /**
     * Check if profile is complete
     */
    public function checkProfileCompletion(): void
    {
        $this->profileCompleted = !empty($this->firstName)
            && !empty($this->lastName)
            && !empty($this->phoneNumber)
            && !empty($this->avatarName);
    }
}
