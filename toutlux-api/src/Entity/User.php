<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use App\Repository\UserRepository;
use App\State\UserMeProvider;
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
            uriTemplate: '/users/me',
            normalizationContext: ['groups' => ['user:own', 'user:private']],
            security: "object == user",
            provider: UserMeProvider::class
        ),
        new Get(
            uriTemplate: '/users/{id}',
            normalizationContext: ['groups' => ['user:seller']],
            security: "is_granted('ROLE_USER')"
        ),
        new Put(
            denormalizationContext: ['groups' => ['user:write']],
            security: "object == user"
        ),
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // ==============================================
    // GROUPES DE SÉRIALISATION :
    // - user:seller     -> Informations vendeur (visible par utilisateurs connectés)
    // - user:own        -> Informations complètes (propriétaire du compte uniquement)
    // - user:private    -> Informations sensibles (propriétaire uniquement)
    // - user:write      -> Champs modifiables
    // - house:read      -> Champs nécessaires dans les relations House
    // ==============================================

    // =============================================
    // PROPRIÉTÉS DE BASE
    // =============================================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:seller', 'user:own', 'house:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:private'])] // ❌ Email JAMAIS visible pour les autres
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Please enter a valid email address')]
    private ?string $email = null;

    #[ORM\Column]
    #[Groups(['user:write'])] // ❌ Password jamais exposé en lecture
    #[Assert\NotBlank(message: 'Password is required')]
    private ?string $password = null;

    #[ORM\Column]
    #[Groups(['user:private'])] // ❌ Rôles privés
    private array $roles = [];

    // =============================================
    // INFORMATIONS PERSONNELLES
    // =============================================

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:seller', 'user:own', 'user:write'])] // ✅ Visible pour vendeurs
    #[Assert\Length(min: 2, max: 255, minMessage: 'First name must be at least 2 characters')]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:seller', 'user:own', 'user:write'])] // ✅ Visible pour vendeurs
    #[Assert\Length(min: 2, max: 255, minMessage: 'Last name must be at least 2 characters')]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['user:private', 'user:write'])] // ❌ Téléphone PRIVÉ
    #[Assert\Regex(
        pattern: '/^\d{4,15}$/',
        message: 'Format de numéro invalide. Saisissez uniquement le numéro local sans indicatif.'
    )]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 5, nullable: true)]
    #[Groups(['user:private', 'user:write'])] // ❌ Indicatif PRIVÉ
    #[Assert\Regex(
        pattern: '/^\d{1,4}$/',
        message: 'Indicatif invalide. Il doit comporter entre 1 et 4 chiffres.'
    )]
    private ?string $phoneNumberIndicatif = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:seller', 'user:own', 'user:write'])] // ✅ Photo visible pour vendeurs
    private ?string $profilePicture = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['user:seller', 'user:own', 'user:write'])] // ✅ Type visible pour vendeurs
    #[Assert\Choice(choices: ['tenant', 'landlord', 'both', 'agent'], message: 'Invalid user type')]
    private ?string $userType = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:private', 'user:write'])] // ❌ Occupation PRIVÉE
    private ?string $occupation = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Groups(['user:private', 'user:write'])] // ❌ Source de revenus PRIVÉE
    #[Assert\Choice(choices: ['salary', 'business', 'investment', 'pension', 'rental', 'other'])]
    private ?string $incomeSource = null;

    // =============================================
    // DOCUMENTS - TOUS PRIVÉS
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
    // STATUTS DE VÉRIFICATION - VENDEUR
    // =============================================

    #[ORM\Column]
    #[Groups(['user:seller', 'user:own'])] // ✅ Statuts visibles pour vendeurs
    private ?bool $isEmailVerified = false;

    #[ORM\Column]
    #[Groups(['user:seller', 'user:own'])]
    private ?bool $isPhoneVerified = false;

    #[ORM\Column]
    #[Groups(['user:seller', 'user:own'])]
    private ?bool $isIdentityVerified = false;

    #[ORM\Column]
    #[Groups(['user:seller', 'user:own'])]
    private ?bool $isFinancialDocsVerified = false;

    // ❌ Dates de vérification PRIVÉES
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:private'])]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:private'])]
    private ?\DateTimeImmutable $phoneVerifiedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:private'])]
    private ?\DateTimeImmutable $identityVerifiedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:private'])]
    private ?\DateTimeImmutable $financialDocsVerifiedAt = null;

    // =============================================
    // SYSTÈME EMAIL - TOUT PRIVÉ
    // =============================================

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:private'])]
    private ?string $emailConfirmationToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:private'])]
    private ?\DateTimeImmutable $emailConfirmationTokenExpiresAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user:private'])]
    private ?int $emailVerificationAttempts = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:private'])]
    private ?\DateTimeImmutable $lastEmailVerificationRequestAt = null;

    // =============================================
    // TERMES - VENDEUR (pour confiance)
    // =============================================

    #[ORM\Column]
    #[Groups(['user:seller', 'user:own'])] // ✅ Visible pour vendeurs (score confiance)
    private ?bool $termsAccepted = false;

    #[ORM\Column]
    #[Groups(['user:seller', 'user:own'])] // ✅ Visible pour vendeurs (score confiance)
    private ?bool $privacyAccepted = false;

    #[ORM\Column]
    #[Groups(['user:private', 'user:write'])] // ❌ Marketing privé
    private ?bool $marketingAccepted = false;

    // ❌ Dates d'acceptation PRIVÉES
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
    #[Groups(['user:seller', 'user:own'])] // ✅ Date d'inscription visible (membre depuis)
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['user:private'])] // ❌ Dernière mise à jour PRIVÉE
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 20)]
    #[Groups(['user:private'])] // ❌ Statut du compte PRIVÉ
    private ?string $status = 'pending_verification';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:private'])] // ❌ Dernière activité PRIVÉE
    private ?\DateTimeImmutable $lastActiveAt = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['user:private', 'user:write'])] // ❌ Langue PRIVÉE
    private ?string $language = 'fr';

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:private'])] // ❌ Google ID PRIVÉ
    private ?string $googleId = null;

    #[ORM\Column]
    #[Groups(['user:seller', 'user:own'])] // ✅ Vues du profil visible
    private ?int $profileViews = 0;

    // =============================================
    // RELATIONS
    // =============================================

    #[ORM\OneToMany(targetEntity: House::class, mappedBy: 'user', orphanRemoval: true)]
    #[Groups(['user:seller', 'user:own'])] // ✅ Relations houses visibles
    private Collection $houses;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $messages;

    // =============================================
    // CONSTRUCTEUR
    // =============================================

    public function __construct()
    {
        $this->houses = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->messages = new ArrayCollection();
        $this->roles = ['ROLE_USER'];
        $this->profileViews = 0;
        $this->emailVerificationAttempts = 0;
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
    // GETTERS ET SETTERS DE BASE
    // =============================================

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

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
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

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;
        return $this;
    }

    public function getUserType(): ?string
    {
        return $this->userType;
    }

    public function setUserType(?string $userType): static
    {
        $this->userType = $userType;
        return $this;
    }

    public function getOccupation(): ?string
    {
        return $this->occupation;
    }

    public function setOccupation(?string $occupation): static
    {
        $this->occupation = $occupation;
        return $this;
    }

    public function getIncomeSource(): ?string
    {
        return $this->incomeSource;
    }

    public function setIncomeSource(?string $incomeSource): static
    {
        $this->incomeSource = $incomeSource;
        return $this;
    }

    // =============================================
    // GETTERS/SETTERS DOCUMENTS
    // =============================================

    public function getIdentityCardType(): ?string
    {
        return $this->identityCardType;
    }

    public function setIdentityCardType(?string $identityCardType): static
    {
        $this->identityCardType = $identityCardType;
        return $this;
    }

    public function getIdentityCard(): ?string
    {
        return $this->identityCard;
    }

    public function setIdentityCard(?string $identityCard): static
    {
        $this->identityCard = $identityCard;
        return $this;
    }

    public function getSelfieWithId(): ?string
    {
        return $this->selfieWithId;
    }

    public function setSelfieWithId(?string $selfieWithId): static
    {
        $this->selfieWithId = $selfieWithId;
        return $this;
    }

    public function getIncomeProof(): ?string
    {
        return $this->incomeProof;
    }

    public function setIncomeProof(?string $incomeProof): static
    {
        $this->incomeProof = $incomeProof;
        return $this;
    }

    public function getOwnershipProof(): ?string
    {
        return $this->ownershipProof;
    }

    public function setOwnershipProof(?string $ownershipProof): static
    {
        $this->ownershipProof = $ownershipProof;
        return $this;
    }

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setUser($this);
        }
        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getUser() === $this) {
                $message->setUser(null);
            }
        }
        return $this;
    }

    // =============================================
    // GETTERS/SETTERS VÉRIFICATIONS
    // =============================================

    public function isEmailVerified(): ?bool
    {
        return $this->isEmailVerified;
    }

    public function setIsEmailVerified(bool $isEmailVerified): static
    {
        $this->isEmailVerified = $isEmailVerified;
        if ($isEmailVerified && !$this->emailVerifiedAt) {
            $this->emailVerifiedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function isPhoneVerified(): ?bool
    {
        return $this->isPhoneVerified;
    }

    public function setIsPhoneVerified(bool $isPhoneVerified): static
    {
        $this->isPhoneVerified = $isPhoneVerified;
        if ($isPhoneVerified && !$this->phoneVerifiedAt) {
            $this->phoneVerifiedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function isIdentityVerified(): ?bool
    {
        return $this->isIdentityVerified;
    }

    public function setIsIdentityVerified(bool $isIdentityVerified): static
    {
        $this->isIdentityVerified = $isIdentityVerified;
        if ($isIdentityVerified && !$this->identityVerifiedAt) {
            $this->identityVerifiedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function isFinancialDocsVerified(): ?bool
    {
        return $this->isFinancialDocsVerified;
    }

    public function setIsFinancialDocsVerified(bool $isFinancialDocsVerified): static
    {
        $this->isFinancialDocsVerified = $isFinancialDocsVerified;
        if ($isFinancialDocsVerified && !$this->financialDocsVerifiedAt) {
            $this->financialDocsVerifiedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    // =============================================
    // GETTERS/SETTERS DATES DE VÉRIFICATION
    // =============================================

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?\DateTimeImmutable $emailVerifiedAt): static
    {
        $this->emailVerifiedAt = $emailVerifiedAt;
        return $this;
    }

    public function getPhoneVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->phoneVerifiedAt;
    }

    public function getIdentityVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->identityVerifiedAt;
    }

    public function getFinancialDocsVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->financialDocsVerifiedAt;
    }

    // =============================================
    // GETTERS/SETTERS CONFIRMATION EMAIL
    // =============================================

    public function getEmailConfirmationToken(): ?string
    {
        return $this->emailConfirmationToken;
    }

    public function setEmailConfirmationToken(?string $emailConfirmationToken): static
    {
        $this->emailConfirmationToken = $emailConfirmationToken;
        return $this;
    }

    public function getEmailConfirmationTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->emailConfirmationTokenExpiresAt;
    }

    public function setEmailConfirmationTokenExpiresAt(?\DateTimeImmutable $emailConfirmationTokenExpiresAt): static
    {
        $this->emailConfirmationTokenExpiresAt = $emailConfirmationTokenExpiresAt;
        return $this;
    }

    public function getEmailVerificationAttempts(): ?int
    {
        return $this->emailVerificationAttempts;
    }

    public function setEmailVerificationAttempts(?int $emailVerificationAttempts): static
    {
        $this->emailVerificationAttempts = $emailVerificationAttempts;
        return $this;
    }

    public function getLastEmailVerificationRequestAt(): ?\DateTimeImmutable
    {
        return $this->lastEmailVerificationRequestAt;
    }

    public function setLastEmailVerificationRequestAt(?\DateTimeImmutable $lastEmailVerificationRequestAt): static
    {
        $this->lastEmailVerificationRequestAt = $lastEmailVerificationRequestAt;
        return $this;
    }

    // =============================================
    // GETTERS/SETTERS TERMES
    // =============================================

    public function isTermsAccepted(): ?bool
    {
        return $this->termsAccepted;
    }

    public function setTermsAccepted(?bool $termsAccepted): static
    {
        $this->termsAccepted = $termsAccepted;
        if ($termsAccepted && !$this->termsAcceptedAt) {
            $this->termsAcceptedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function isPrivacyAccepted(): ?bool
    {
        return $this->privacyAccepted;
    }

    public function setPrivacyAccepted(?bool $privacyAccepted): static
    {
        $this->privacyAccepted = $privacyAccepted;
        if ($privacyAccepted && !$this->privacyAcceptedAt) {
            $this->privacyAcceptedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function isMarketingAccepted(): ?bool
    {
        return $this->marketingAccepted;
    }

    public function setMarketingAccepted(?bool $marketingAccepted): static
    {
        $this->marketingAccepted = $marketingAccepted;
        if ($marketingAccepted && !$this->marketingAcceptedAt) {
            $this->marketingAcceptedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getTermsAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->termsAcceptedAt;
    }

    public function getPrivacyAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->privacyAcceptedAt;
    }

    public function getMarketingAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->marketingAcceptedAt;
    }

    // =============================================
    // GETTERS/SETTERS SYSTÈME
    // =============================================

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
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

    public function getLastActiveAt(): ?\DateTimeImmutable
    {
        return $this->lastActiveAt;
    }

    public function setLastActiveAt(?\DateTimeImmutable $lastActiveAt): static
    {
        $this->lastActiveAt = $lastActiveAt;
        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): static
    {
        $this->language = $language;
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

    // =============================================
    // RELATIONS
    // =============================================

    public function getHouses(): Collection
    {
        return $this->houses;
    }

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

    #[Groups(['user:seller', 'user:own'])]
    public function getHousesCount(): int
    {
        return $this->houses->count();
    }

    // =============================================
    // MÉTHODES UserInterface (Symfony Security)
    // =============================================

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
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
        // Nothing to do here
    }

    // =============================================
    // MÉTHODES MÉTIER POUR VENDEURS
    // =============================================

    /**
     * Obtient le statut de validation SIMPLIFIÉ pour les vendeurs
     */
    #[Groups(['user:seller', 'user:own'])]
    public function getValidationStatus(): array
    {
        return [
            'email' => [
                'isVerified' => $this->isEmailVerified,
                // ❌ Pas de date pour les autres utilisateurs
            ],
            'phone' => [
                'isVerified' => $this->isPhoneVerified,
            ],
            'identity' => [
                'isVerified' => $this->isIdentityVerified,
                // ❌ Pas de hasDocuments pour les autres
            ],
            'financialDocs' => [
                'isVerified' => $this->isFinancialDocsVerified,
            ],
            'terms' => [
                'accepted' => $this->isTermsAccepted() && $this->isPrivacyAccepted(),
            ]
        ];
    }

    /**
     * Obtient le nom d'affichage
     */
    #[Groups(['user:seller', 'user:own', 'house:read'])]
    public function getDisplayName(): string
    {
        $fullName = $this->getFullName();
        return $fullName ?: 'Vendeur';
    }

    /**
     * Obtient le nom complet
     */
    #[Groups(['user:seller', 'user:own'])]
    public function getFullName(): string
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }

    /**
     * Calcule un score de vérification sur 100
     */
    #[Groups(['user:seller', 'user:own'])]
    public function getVerificationScore(): int
    {
        $score = 0;

        if ($this->isEmailVerified) $score += 25;
        if ($this->isPhoneVerified) $score += 25;
        if ($this->isIdentityVerified) $score += 25;
        if ($this->isFinancialDocsVerified) $score += 25;

        return $score;
    }

    /**
     * Vérifie si l'utilisateur est entièrement vérifié
     */
    #[Groups(['user:seller', 'user:own'])]
    public function isFullyVerified(): bool
    {
        return $this->isEmailVerified &&
            $this->isPhoneVerified &&
            $this->isIdentityVerified &&
            $this->isFinancialDocsVerified;
    }

    // =============================================
    // MÉTHODES MÉTIER POUR PROPRIÉTAIRE (PRIVÉES)
    // =============================================

    /**
     * Obtient le statut de validation COMPLET (propriétaire uniquement)
     */
    #[Groups(['user:private'])]
    public function getDetailedValidationStatus(): array
    {
        return [
            'email' => [
                'isVerified' => $this->isEmailVerified,
                'verifiedAt' => $this->emailVerifiedAt?->format('c'),
                'required' => true
            ],
            'phone' => [
                'isVerified' => $this->isPhoneVerified,
                'verifiedAt' => $this->phoneVerifiedAt?->format('c'),
                'required' => true
            ],
            'identity' => [
                'isVerified' => $this->isIdentityVerified,
                'verifiedAt' => $this->identityVerifiedAt?->format('c'),
                'required' => false,
                'hasDocuments' => $this->hasIdentityDocuments()
            ],
            'financialDocs' => [
                'isVerified' => $this->isFinancialDocsVerified,
                'verifiedAt' => $this->financialDocsVerifiedAt?->format('c'),
                'required' => false,
                'hasDocuments' => $this->hasFinancialDocuments()
            ],
            'terms' => [
                'accepted' => $this->termsAccepted,
                'acceptedAt' => $this->termsAcceptedAt?->format('c')
            ],
        ];
    }

    /**
     * Vérifie si l'utilisateur a un compte Gmail
     */
    #[Groups(['user:private'])]
    public function isGmailAccount(): bool
    {
        return str_ends_with(strtolower($this->email ?? ''), '@gmail.com');
    }

    /**
     * Vérifie si l'utilisateur a besoin d'une confirmation d'email
     */
    #[Groups(['user:private'])]
    public function isEmailConfirmationRequired(): bool
    {
        return !$this->isEmailVerified && !$this->isGmailAccount();
    }

    /**
     * Vérifie si l'utilisateur peut demander une nouvelle confirmation
     */
    #[Groups(['user:private'])]
    public function isEmailConfirmationRequestAllowed(): bool
    {
        if ($this->isEmailVerified) {
            return false;
        }

        $lastRequest = $this->lastEmailVerificationRequestAt;
        if ($lastRequest && $lastRequest > new \DateTimeImmutable('-1 hour')) {
            $attempts = $this->emailVerificationAttempts ?? 0;
            return $attempts < 3;
        }

        return true;
    }

    /**
     * Obtient le délai avant la prochaine tentative possible
     */
    #[Groups(['user:private'])]
    public function getNextEmailConfirmationAllowedAt(): ?\DateTimeImmutable
    {
        if ($this->isEmailConfirmationRequestAllowed()) {
            return null;
        }

        return $this->lastEmailVerificationRequestAt?->modify('+1 hour');
    }

    /**
     * Vérifie si le token de confirmation est expiré
     */
    #[Groups(['user:private'])]
    public function isEmailConfirmationTokenExpired(): bool
    {
        if (!$this->emailConfirmationTokenExpiresAt) {
            return true;
        }

        return $this->emailConfirmationTokenExpiresAt <= new \DateTimeImmutable();
    }

    /**
     * Vérifie si l'utilisateur a soumis des documents d'identité
     */
    #[Groups(['user:private'])]
    public function hasIdentityDocuments(): bool
    {
        return !empty($this->identityCard) && !empty($this->selfieWithId);
    }

    /**
     * Vérifie si l'utilisateur a soumis des documents financiers
     */
    #[Groups(['user:private'])]
    public function hasFinancialDocuments(): bool
    {
        return !empty($this->incomeProof) || !empty($this->ownershipProof);
    }

    /**
     * Vérifie si l'utilisateur peut créer des annonces
     */
    #[Groups(['user:private'])]
    public function isListingCreationAllowed(): bool
    {
        return $this->isEmailVerified &&
            $this->isPhoneVerified &&
            $this->isIdentityVerified &&
            $this->status === 'active' &&
            $this->termsAccepted &&
            $this->privacyAccepted;
    }

    /**
     * Obtient le statut détaillé de la vérification email
     */
    #[Groups(['user:private'])]
    public function getEmailVerificationStatus(): array
    {
        return [
            'is_verified' => $this->isEmailVerified,
            'is_gmail' => $this->isGmailAccount(),
            'needs_confirmation' => $this->isEmailConfirmationRequired(),
            'can_request_new' => $this->isEmailConfirmationRequestAllowed(),
            'attempts_used' => $this->emailVerificationAttempts ?? 0,
            'next_allowed_at' => $this->getNextEmailConfirmationAllowedAt()?->format('c'),
            'token_expires_at' => $this->emailConfirmationTokenExpiresAt?->format('c'),
            'token_expired' => $this->isEmailConfirmationTokenExpired()
        ];
    }

    /**
     * Obtient des informations sur les documents soumis
     */
    #[Groups(['user:private'])]
    public function getDocumentsStatus(): array
    {
        return [
            'identity' => [
                'submitted' => $this->hasIdentityDocuments(),
                'verified' => $this->isIdentityVerified,
                'type' => $this->identityCardType,
                'verified_at' => $this->identityVerifiedAt?->format('c')
            ],
            'financial' => [
                'submitted' => $this->hasFinancialDocuments(),
                'verified' => $this->isFinancialDocsVerified,
                'income_proof' => !empty($this->incomeProof),
                'ownership_proof' => !empty($this->ownershipProof),
                'income_source' => $this->incomeSource,
                'verified_at' => $this->financialDocsVerifiedAt?->format('c')
            ]
        ];
    }

    /**
     * Vérifie si le profil est complet
     */
    #[Groups(['user:private'])]
    public function isProfileComplete(): bool
    {
        // Champs requis
        $requiredFields = [
            $this->firstName,
            $this->lastName,
            $this->phoneNumber,
            $this->phoneNumberIndicatif,
            $this->profilePicture,
            $this->identityCardType,
            $this->identityCard,
            $this->selfieWithId,
        ];

        // Vérifier que tous les champs requis sont remplis
        foreach ($requiredFields as $field) {
            if (empty($field) || $field === 'yes') {
                return false;
            }
        }

        // Vérifications email et téléphone obligatoires
        if (!$this->isEmailVerified || !$this->isPhoneVerified) {
            return false;
        }

        // Vérifier qu'au moins un document financier est présent
        if (!$this->hasFinancialDocuments()) {
            return false;
        }

        // Vérifier les termes
        if (!$this->termsAccepted || !$this->privacyAccepted) {
            return false;
        }

        return true;
    }

    /**
     * Calcule le pourcentage de completion du profil
     */
    #[Groups(['user:private'])]
    public function getCompletionPercentage(): int
    {
        $completedSteps = 0;
        $totalSteps = 5;

        // GROUPE 1: Informations personnelles (avec validation email)
        $personalInfoComplete = $this->firstName &&
            $this->lastName &&
            $this->phoneNumber &&
            $this->phoneNumberIndicatif &&
            $this->profilePicture &&
            $this->profilePicture !== 'yes' &&
            $this->isEmailVerified;

        if ($personalInfoComplete) {
            $completedSteps++;
        }

        // GROUPE 2: Documents d'identité
        if ($this->hasIdentityDocuments()) {
            $completedSteps++;
        }

        // GROUPE 3: Documents financiers
        if ($this->hasFinancialDocuments()) {
            $completedSteps++;
        }

        // GROUPE 4: Termes et conditions
        if ($this->termsAccepted && $this->privacyAccepted) {
            $completedSteps++;
        }

        // GROUPE 5: Vérifications (email + phone obligatoires)
        if ($this->isEmailVerified && $this->isPhoneVerified) {
            $completedSteps++;
        }

        return round(($completedSteps / $totalSteps) * 100);
    }

    /**
     * Obtient la liste des champs manquants
     */
    #[Groups(['user:private'])]
    public function getMissingFields(): array
    {
        $missing = [];

        // Informations personnelles
        if (!$this->firstName) $missing[] = 'firstName';
        if (!$this->lastName) $missing[] = 'lastName';
        if (!$this->phoneNumber) $missing[] = 'phoneNumber';
        if (!$this->phoneNumberIndicatif) $missing[] = 'phoneNumberIndicatif';
        if (!$this->profilePicture || $this->profilePicture === 'yes') $missing[] = 'profilePicture';

        // Documents d'identité
        if (!$this->identityCardType) $missing[] = 'identityCardType';
        if (!$this->identityCard) $missing[] = 'identityCard';
        if (!$this->selfieWithId) $missing[] = 'selfieWithId';

        // Documents financiers (au moins un requis)
        if (!$this->hasFinancialDocuments()) {
            $missing[] = 'financialDocs';
        }

        // Vérifications (obligatoires pour completion)
        if (!$this->isEmailVerified) $missing[] = 'emailVerification';
        if (!$this->isPhoneVerified) $missing[] = 'phoneVerification';

        // Termes
        if (!$this->termsAccepted) $missing[] = 'termsAccepted';
        if (!$this->privacyAccepted) $missing[] = 'privacyAccepted';

        return $missing;
    }

    /**
     * Vérifie si l'utilisateur a des documents d'identité soumis mais non vérifiés
     */
    #[Groups(['user:private'])]
    public function hasPendingIdentityValidation(): bool
    {
        return $this->hasIdentityDocuments() && !$this->isIdentityVerified;
    }

    /**
     * Vérifie si l'utilisateur a des documents financiers soumis mais non vérifiés
     */
    #[Groups(['user:private'])]
    public function hasPendingFinancialValidation(): bool
    {
        return $this->hasFinancialDocuments() && !$this->isFinancialDocsVerified;
    }

    /**
     * Obtient le nombre total de validations en attente
     */
    #[Groups(['user:private'])]
    public function getPendingValidationsCount(): int
    {
        $count = 0;
        if ($this->hasPendingIdentityValidation()) $count++;
        if ($this->hasPendingFinancialValidation()) $count++;
        return $count;
    }

    /**
     * Obtient les types de validations en attente
     */
    #[Groups(['user:private'])]
    public function getPendingValidationTypes(): array
    {
        $types = [];
        if ($this->hasPendingIdentityValidation()) $types[] = 'identity';
        if ($this->hasPendingFinancialValidation()) $types[] = 'financial';
        return $types;
    }

    /**
     * Méthode de debug pour la completion du profil
     */
    #[Groups(['user:private'])]
    public function getCompletionDebug(): array
    {
        return [
            'personal_info' => [
                'firstName' => !empty($this->firstName),
                'lastName' => !empty($this->lastName),
                'phoneNumber' => !empty($this->phoneNumber),
                'phoneNumberIndicatif' => !empty($this->phoneNumberIndicatif),
                'profilePicture' => !empty($this->profilePicture) && $this->profilePicture !== 'yes',
                'emailVerified' => $this->isEmailVerified,
                'complete' => !empty($this->firstName) &&
                    !empty($this->lastName) &&
                    !empty($this->phoneNumber) &&
                    !empty($this->phoneNumberIndicatif) &&
                    !empty($this->profilePicture) &&
                    $this->profilePicture !== 'yes' &&
                    $this->isEmailVerified
            ],
            'identity_docs' => [
                'identityCardType' => !empty($this->identityCardType),
                'identityCard' => !empty($this->identityCard),
                'selfieWithId' => !empty($this->selfieWithId),
                'identityVerified' => $this->isIdentityVerified,
                'complete' => $this->hasIdentityDocuments()
            ],
            'financial_docs' => [
                'incomeProof' => !empty($this->incomeProof),
                'ownershipProof' => !empty($this->ownershipProof),
                'complete' => $this->hasFinancialDocuments()
            ],
            'terms' => [
                'termsAccepted' => $this->termsAccepted,
                'privacyAccepted' => $this->privacyAccepted,
                'complete' => $this->termsAccepted && $this->privacyAccepted
            ],
            'verifications' => [
                'isEmailVerified' => $this->isEmailVerified,
                'isPhoneVerified' => $this->isPhoneVerified,
                'isIdentityVerified' => $this->isIdentityVerified,
                'complete' => $this->isEmailVerified && $this->isPhoneVerified
            ],
            'validation_status' => $this->getDetailedValidationStatus(),
            'summary' => [
                'completion_percentage' => $this->getCompletionPercentage(),
                'is_profile_complete' => $this->isProfileComplete(),
                'missing_fields' => $this->getMissingFields()
            ]
        ];
    }

    // =============================================
    // MÉTHODES UTILITAIRES
    // =============================================

    /**
     * Accepte tous les termes et conditions
     */
    public function acceptAllTerms(string $version = '1.0'): static
    {
        $now = new \DateTimeImmutable();

        $this->setTermsAccepted(true);
        $this->setPrivacyAccepted(true);

        if (!$this->termsAcceptedAt) {
            $this->termsAcceptedAt = $now;
        }

        if (!$this->privacyAcceptedAt) {
            $this->privacyAcceptedAt = $now;
        }

        return $this;
    }

    /**
     * Méthodes de compatibilité (pour ne pas casser le code existant)
     */
    public function needsEmailConfirmation(): bool
    {
        return $this->isEmailConfirmationRequired();
    }

    public function isCanCreateListing(): bool
    {
        return $this->isListingCreationAllowed();
    }

    /**
     * Représentation string de l'utilisateur
     */
    public function __toString(): string
    {
        return $this->getDisplayName();
    }
}
