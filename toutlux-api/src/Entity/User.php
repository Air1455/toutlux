<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(
            security: "object == user",
            normalizationContext: ['groups' => ['user:read', 'user:private']]
        ),
        new Put(
            security: "object == user",
            denormalizationContext: ['groups' => ['user:write']]
        ),
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // =============================================
    // PROPRIÉTÉS DE BASE
    // =============================================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:public', 'user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:read', 'user:write'])]
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Please enter a valid email address')]
    private ?string $email = null;

    #[ORM\Column]
    #[Groups(['user:write'])]
    #[Assert\NotBlank(message: 'Password is required')]
    private ?string $password = null;

    #[ORM\Column]
    #[Groups(['user:private'])]
    private array $roles = [];

    // =============================================
    // INFORMATIONS PERSONNELLES
    // =============================================

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:public', 'user:read', 'user:write'])]
    #[Assert\Length(min: 2, max: 255, minMessage: 'First name must be at least 2 characters')]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:public', 'user:read', 'user:write'])]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Last name must be at least 2 characters')]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['user:private', 'user:write'])]
    #[Assert\Regex(
        pattern: '/^\d{4,15}$/',
        message: 'Format de numéro invalide. Saisissez uniquement le numéro local sans indicatif.'
    )]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 5, nullable: true)]
    #[Groups(['user:private', 'user:write'])]
    #[Assert\Regex(
        pattern: '/^\d{1,4}$/',
        message: 'Indicatif invalide. Il doit comporter entre 1 et 4 chiffres.'
    )]
    private ?string $phoneNumberIndicatif = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:public', 'user:read', 'user:write'])]
    private ?string $profilePicture = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['user:public', 'user:read', 'user:write'])]
    #[Assert\Choice(choices: ['tenant', 'landlord', 'both', 'agent'], message: 'Invalid user type')]
    private ?string $userType = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:private', 'user:write'])]
    private ?string $occupation = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Groups(['user:private', 'user:write'])]
    #[Assert\Choice(choices: ['salary', 'business', 'investment', 'pension', 'rental', 'other'])]
    private ?string $incomeSource = null;

    // =============================================
    // DOCUMENTS
    // =============================================

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['user:private', 'user:write'])]
    #[Assert\Choice(choices: ['national_id', 'passport', 'driving_license'])]
    private ?string $identityCardType = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:private', 'user:write'])]
    private ?string $identityCard = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:private', 'user:write'])]
    private ?string $selfieWithId = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:private', 'user:write'])]
    private ?string $incomeProof = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:private', 'user:write'])]
    private ?string $ownershipProof = null;

    // =============================================
    // STATUTS DE VÉRIFICATION
    // =============================================

    #[ORM\Column]
    #[Groups(['user:public', 'user:read'])]
    private ?bool $isEmailVerified = false;

    #[ORM\Column]
    #[Groups(['user:public', 'user:read'])]
    private ?bool $isPhoneVerified = false;

    #[ORM\Column]
    #[Groups(['user:public', 'user:read'])]
    private ?bool $isIdentityVerified = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:private'])]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:private'])]
    private ?\DateTimeImmutable $phoneVerifiedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:private'])]
    private ?\DateTimeImmutable $identityVerifiedAt = null;

    // =============================================
    // TERMES ET CONDITIONS
    // =============================================

    #[ORM\Column]
    #[Groups(['user:private', 'user:write'])]
    private ?bool $termsAccepted = false;

    #[ORM\Column]
    #[Groups(['user:private', 'user:write'])]
    private ?bool $privacyAccepted = false;

    #[ORM\Column]
    #[Groups(['user:private', 'user:write'])]
    private ?bool $marketingAccepted = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:private'])]
    private ?\DateTimeImmutable $termsAcceptedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:private'])]
    private ?\DateTimeImmutable $privacyAcceptedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:private'])]
    private ?\DateTimeImmutable $marketingAcceptedAt = null;

    // =============================================
    // MÉTADONNÉES SYSTÈME
    // =============================================

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['user:private'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 20)]
    #[Groups(['user:private'])]
    private ?string $status = 'pending_verification';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:private'])]
    private ?\DateTimeImmutable $lastActiveAt = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['user:private', 'user:write'])]
    private ?string $language = 'fr';

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:private'])]
    private ?string $googleId = null;

    // =============================================
    // RELATIONS
    // =============================================

    #[ORM\OneToMany(targetEntity: House::class, mappedBy: 'user', orphanRemoval: true)]
    #[Groups(['user:public'])]
    private Collection $houses;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $notifications;

    #[ORM\Column]
    #[Groups(['user:public', 'user:read'])]
    private ?int $profileViews = null;

    // =============================================
    // CONSTRUCTEUR
    // =============================================

    public function __construct()
    {
        $this->houses = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
    }

    // =============================================
    // CALLBACKS DOCTRINE
    // =============================================

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // =============================================
    // GETTERS ET SETTERS (simplifiés - seulement l'essentiel)
    // =============================================

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(?string $firstName): static { $this->firstName = $firstName; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(?string $lastName): static { $this->lastName = $lastName; return $this; }

    public function getPhoneNumber(): ?string { return $this->phoneNumber; }
    public function setPhoneNumber(?string $phoneNumber): static { $this->phoneNumber = $phoneNumber; return $this; }

    public function getProfilePicture(): ?string { return $this->profilePicture; }
    public function setProfilePicture(?string $profilePicture): static { $this->profilePicture = $profilePicture; return $this; }

    public function getUserType(): ?string { return $this->userType; }
    public function setUserType(?string $userType): static { $this->userType = $userType; return $this; }

    public function getOccupation(): ?string { return $this->occupation; }
    public function setOccupation(?string $occupation): static { $this->occupation = $occupation; return $this; }

    public function getIncomeSource(): ?string { return $this->incomeSource; }
    public function setIncomeSource(?string $incomeSource): static { $this->incomeSource = $incomeSource; return $this; }

    public function getIdentityCardType(): ?string { return $this->identityCardType; }
    public function setIdentityCardType(?string $identityCardType): static { $this->identityCardType = $identityCardType; return $this; }

    public function getIdentityCard(): ?string { return $this->identityCard; }
    public function setIdentityCard(?string $identityCard): static { $this->identityCard = $identityCard; return $this; }

    public function getSelfieWithId(): ?string { return $this->selfieWithId; }
    public function setSelfieWithId(?string $selfieWithId): static { $this->selfieWithId = $selfieWithId; return $this; }

    public function getIncomeProof(): ?string { return $this->incomeProof; }
    public function setIncomeProof(?string $incomeProof): static { $this->incomeProof = $incomeProof; return $this; }

    public function getOwnershipProof(): ?string { return $this->ownershipProof; }
    public function setOwnershipProof(?string $ownershipProof): static { $this->ownershipProof = $ownershipProof; return $this; }

    public function isEmailVerified(): ?bool { return $this->isEmailVerified; }
    public function setIsEmailVerified(bool $isEmailVerified): static
    {
        $this->isEmailVerified = $isEmailVerified;
        if ($isEmailVerified && !$this->emailVerifiedAt) {
            $this->emailVerifiedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function isPhoneVerified(): ?bool { return $this->isPhoneVerified; }
    public function setIsPhoneVerified(bool $isPhoneVerified): static
    {
        $this->isPhoneVerified = $isPhoneVerified;
        if ($isPhoneVerified && !$this->phoneVerifiedAt) {
            $this->phoneVerifiedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function isIdentityVerified(): ?bool { return $this->isIdentityVerified; }
    public function setIsIdentityVerified(bool $isIdentityVerified): static
    {
        $this->isIdentityVerified = $isIdentityVerified;
        if ($isIdentityVerified && !$this->identityVerifiedAt) {
            $this->identityVerifiedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function isTermsAccepted(): ?bool { return $this->termsAccepted; }
    public function setTermsAccepted(?bool $termsAccepted): static
    {
        $this->termsAccepted = $termsAccepted;
        if ($termsAccepted && !$this->termsAcceptedAt) {
            $this->termsAcceptedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function isPrivacyAccepted(): ?bool { return $this->privacyAccepted; }
    public function setPrivacyAccepted(?bool $privacyAccepted): static
    {
        $this->privacyAccepted = $privacyAccepted;
        if ($privacyAccepted && !$this->privacyAcceptedAt) {
            $this->privacyAcceptedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function isMarketingAccepted(): ?bool { return $this->marketingAccepted; }
    public function setMarketingAccepted(?bool $marketingAccepted): static
    {
        $this->marketingAccepted = $marketingAccepted;
        if ($marketingAccepted && !$this->marketingAcceptedAt) {
            $this->marketingAcceptedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getGoogleId(): ?string { return $this->googleId; }
    public function setGoogleId(?string $googleId): static { $this->googleId = $googleId; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function getLastActiveAt(): ?\DateTimeImmutable { return $this->lastActiveAt; }
    public function setLastActiveAt(?\DateTimeImmutable $lastActiveAt): static { $this->lastActiveAt = $lastActiveAt; return $this; }

    public function getLanguage(): ?string { return $this->language; }
    public function setLanguage(?string $language): static { $this->language = $language; return $this; }

    // Getters pour les dates de vérification (lecture seule)
    public function getEmailVerifiedAt(): ?\DateTimeImmutable { return $this->emailVerifiedAt; }
    public function getPhoneVerifiedAt(): ?\DateTimeImmutable { return $this->phoneVerifiedAt; }
    public function getIdentityVerifiedAt(): ?\DateTimeImmutable { return $this->identityVerifiedAt; }
    public function getTermsAcceptedAt(): ?\DateTimeImmutable { return $this->termsAcceptedAt; }
    public function getPrivacyAcceptedAt(): ?\DateTimeImmutable { return $this->privacyAcceptedAt; }
    public function getMarketingAcceptedAt(): ?\DateTimeImmutable { return $this->marketingAcceptedAt; }

    // =============================================
    // RELATIONS
    // =============================================

    public function getHouses(): Collection { return $this->houses; }

    public function addHouse(House $house): static
    {
        if (!$this->houses->contains($house)) {
            $this->houses->add($house);
            $house->setUser($this);
        }
        return $this;
    }

    public function removeHouse(House $house): static
    {
        if ($this->houses->removeElement($house)) {
            if ($house->getUser() === $this) {
                $house->setUser(null);
            }
        }
        return $this;
    }

    #[Groups(['user:public', 'user:read'])]
    public function getHousesCount(): int
    {
        return $this->houses->count();
    }

    // =============================================
    // MÉTHODES UserInterface (Symfony Security)
    // =============================================

    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function eraseCredentials(): void {}

    // =============================================
    // MÉTHODES ESSENTIELLES CALCULÉES
    // =============================================

    #[Groups(['user:public', 'user:read'])]
    public function getFullName(): string
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }

    #[Groups(['user:public', 'user:read'])]
    public function getDisplayName(): string
    {
        $fullName = $this->getFullName();
        return $fullName ?: $this->email;
    }

    #[Groups(['user:public', 'user:read'])]
    public function isProfileComplete(): bool
    {
        return $this->firstName &&
            $this->lastName &&
            $this->phoneNumber &&
            $this->profilePicture &&
            $this->isEmailVerified &&
            $this->isPhoneVerified &&
            $this->identityCardType &&
            $this->identityCard &&
            $this->selfieWithId &&
            ($this->incomeProof || $this->ownershipProof) &&
            $this->termsAccepted &&
            $this->privacyAccepted;
    }

    #[Groups(['user:private', 'user:read'])]
    public function getCompletionPercentage(): int
    {
        $requiredFields = [
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'phoneNumber' => $this->phoneNumber,
            'profilePicture' => $this->profilePicture,
            'isEmailVerified' => $this->isEmailVerified,
            'isPhoneVerified' => $this->isPhoneVerified,
            'identityCardType' => $this->identityCardType,
            'identityCard' => $this->identityCard,
            'selfieWithId' => $this->selfieWithId,
            'termsAccepted' => $this->termsAccepted,
            'privacyAccepted' => $this->privacyAccepted,
        ];

        $hasFinancialDoc = $this->incomeProof || $this->ownershipProof;
        $completed = 0;
        $totalFields = count($requiredFields) + 1;

        foreach ($requiredFields as $value) {
            if ($value === true || ($value !== null && $value !== false && $value !== '')) {
                $completed++;
            }
        }

        if ($hasFinancialDoc) {
            $completed++;
        }

        return round(($completed / $totalFields) * 100);
    }

    #[Groups(['user:private', 'user:read'])]
    public function getMissingFields(): array
    {
        $missing = [];

        if (!$this->firstName) $missing[] = 'firstName';
        if (!$this->lastName) $missing[] = 'lastName';
        if (!$this->phoneNumber) $missing[] = 'phoneNumber';
        if (!$this->profilePicture) $missing[] = 'profilePicture';
        if (!$this->isEmailVerified) $missing[] = 'emailVerification';
        if (!$this->isPhoneVerified) $missing[] = 'phoneVerification';
        if (!$this->identityCardType) $missing[] = 'identityCardType';
        if (!$this->identityCard) $missing[] = 'identityCard';
        if (!$this->selfieWithId) $missing[] = 'selfieWithId';
        if (!$this->incomeProof && !$this->ownershipProof) $missing[] = 'financialDocs';
        if (!$this->termsAccepted) $missing[] = 'termsAccepted';
        if (!$this->privacyAccepted) $missing[] = 'privacyAccepted';

        return $missing;
    }

    #[Groups(['user:public', 'user:read'])]
    public function isCanCreateListing(): bool
    {
        return $this->isEmailVerified &&
            $this->isPhoneVerified &&
            $this->isIdentityVerified &&
            $this->status === 'active' &&
            $this->termsAccepted &&
            $this->privacyAccepted;
    }

    public function acceptAllTerms(string $version = '1.0'): static
    {
        $now = new \DateTimeImmutable();

        $this->setTermsAccepted(true);
        $this->setPrivacyAccepted(true);

        return $this;
    }

    public function __toString(): string
    {
        return $this->getDisplayName();
    }

    public function getPhoneNumberIndicatif(): ?string
    {
        return $this->phoneNumberIndicatif;
    }

    public function setPhoneNumberIndicatif(?string $phoneNumberIndicatif): static
    {
        $this->phoneNumberIndicatif = $phoneNumberIndicatif;

        return $this;
    }

    public function getProfileViews(): ?int
    {
        return $this->profileViews;
    }

    public function setProfileViews(int $profileViews): static
    {
        $this->profileViews = $profileViews;

        return $this;
    }
}
