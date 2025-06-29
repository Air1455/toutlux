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
use Symfony\Component\HttpFoundation\File\File; // Import File class
use Vich\UploaderBundle\Mapping\Annotation as Vich; // Import Vich annotations
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable] // Add this annotation
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/users/me',
            normalizationContext: ['groups' => ['user:own', 'user:private']],
            security: "object == user",
            // provider: UserMeProvider::class // This provider is not in the new backend, will need to be handled differently or removed
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
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:seller', 'user:own', 'house:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:private'])]
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Please enter a valid email address')]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(['user:private'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Groups(['user:write'])]
    #[Assert\NotBlank(message: 'Password is required')]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:seller', 'user:own', 'user:write'])]
    #[Assert\Length(min: 2, max: 255, minMessage: 'First name must be at least 2 characters')]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:seller', 'user:own', 'user:write'])]
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
    #[Groups(['user:seller', 'user:own', 'user:write'])]
    private ?string $profilePicture = null;

    // NOTE: This is not a mapped field of entity metadata, just a container to facilitate temporary file upload
    #[Vich\UploadableField(mapping: 'user_image', fileNameProperty: 'profilePicture')]
    private ?File $profilePictureFile = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['user:private', 'user:write'])]
    #[Assert\Choice(choices: ['tenant', 'landlord', 'both', 'agent'], message: 'Invalid user type')]
    private ?string $userType = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:private', 'user:write'])]
    private ?string $occupation = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Groups(['user:private', 'user:write'])]
    #[Assert\Choice(choices: ['salary', 'business', 'investment', 'pension', 'rental', 'other'])]
    private ?string $incomeSource = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['user:private', 'user:write'])]
    #[Assert\Choice(choices: ['national_id', 'passport', 'driving_license'])]
    private ?string $identityCardType = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:private', 'user:write'])]
    private ?string $identityCard = null;

    // NOTE: This is not a mapped field of entity metadata, just a container to facilitate temporary file upload
    #[Vich\UploadableField(mapping: 'user_image', fileNameProperty: 'identityCard')]
    private ?File $identityCardFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:private', 'user:write'])]
    private ?string $selfieWithId = null;

    // NOTE: This is not a mapped field of entity metadata, just a container to facilitate temporary file upload
    #[Vich\UploadableField(mapping: 'user_image', fileNameProperty: 'selfieWithId')]
    private ?File $selfieWithIdFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:private', 'user:write'])]
    private ?string $incomeProof = null;

    // NOTE: This is not a mapped field of entity metadata, just a container to facilitate temporary file upload
    #[Vich\UploadableField(mapping: 'user_image', fileNameProperty: 'incomeProof')]
    private ?File $incomeProofFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:private', 'user:write'])]
    private ?string $ownershipProof = null;

    // NOTE: This is not a mapped field of entity metadata, just a container to facilitate temporary file upload
    #[Vich\UploadableField(mapping: 'user_image', fileNameProperty: 'ownershipProof')]
    private ?File $ownershipProofFile = null;

    #[ORM\Column]
    #[Groups(['user:seller', 'user:own'])]
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

    #[ORM\Column]
    #[Groups(['user:seller', 'user:own'])]
    private ?bool $termsAccepted = false;

    #[ORM\Column]
    #[Groups(['user:seller', 'user:own'])]
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

    #[ORM\Column]
    #[Groups(['user:seller', 'user:own'])]
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

    #[ORM\Column]
    #[Groups(['user:seller', 'user:own'])]
    private ?int $profileViews = 0;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['user:private'])]
    private array $metadata = [];

    #[ORM\OneToMany(targetEntity: House::class, mappedBy: 'user', orphanRemoval: true)]
    #[Groups(['user:seller', 'user:own'])]
    private Collection $houses;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $messages;

    public function __construct()
    {
        $this->houses = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->profileViews = 0;
        $this->emailVerificationAttempts = 0;
        $this->metadata = [];
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
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

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance of
     * UploadedFile can be injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of File object.
     */
    public function setProfilePictureFile(?File $profilePictureFile = null): void
    {
        $this->profilePictureFile = $profilePictureFile;

        if (null !== $profilePictureFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getProfilePictureFile(): ?File
    {
        return $this->profilePictureFile;
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

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance of
     * UploadedFile can be injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of File object.
     */
    public function setIdentityCardFile(?File $identityCardFile = null): void
    {
        $this->identityCardFile = $identityCardFile;

        if (null !== $identityCardFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getIdentityCardFile(): ?File
    {
        return $this->identityCardFile;
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

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance of
     * UploadedFile can be injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of File object.
     */
    public function setSelfieWithIdFile(?File $selfieWithIdFile = null): void
    {
        $this->selfieWithIdFile = $selfieWithIdFile;

        if (null !== $selfieWithIdFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getSelfieWithIdFile(): ?File
    {
        return $this->selfieWithIdFile;
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

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance of
     * UploadedFile can be injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of File object.
     */
    public function setIncomeProofFile(?File $incomeProofFile = null): void
    {
        $this->incomeProofFile = $incomeProofFile;

        if (null !== $incomeProofFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getIncomeProofFile(): ?File
    {
        return $this->incomeProofFile;
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

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance of
     * UploadedFile can be injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of File object.
     */
    public function setOwnershipProofFile(?File $ownershipProofFile = null): void
    {
        $this->ownershipProofFile = $ownershipProofFile;

        if (null !== $ownershipProofFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getOwnershipProofFile(): ?File
    {
        return $this->ownershipProofFile;
    }

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

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

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

    public function getDisplayName(): string
    {
        $fullName = trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
        return $fullName ?: 'Utilisateur';
    }

    public function __toString(): string
    {
        return $this->getEmail();
    }

    public function hasIdentityDocuments(): bool
    {
        return !empty($this->identityCard) && !empty($this->selfieWithId);
    }

    public function hasFinancialDocuments(): bool
    {
        return !empty($this->incomeProof) || !empty($this->ownershipProof);
    }
}
Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\File\File; // Import File class
use Vich\UploaderBundle\Mapping\Annotation as Vich; // Import Vich annotations

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable] // Add this annotation
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $phoneNumberIndicatif = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    // NOTE: This is not a mapped field of entity metadata, just a container to facilitate temporary file upload
    #[Vich\UploadableField(mapping: 'user_image', fileNameProperty: 'profilePicture')]
    private ?File $profilePictureFile = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $userType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $occupation = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $incomeSource = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $identityCardType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $identityCard = null;

    // NOTE: This is not a mapped field of entity metadata, just a container to facilitate temporary file upload
    #[Vich\UploadableField(mapping: 'user_image', fileNameProperty: 'identityCard')]
    private ?File $identityCardFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $selfieWithId = null;

    // NOTE: This is not a mapped field of entity metadata, just a container to facilitate temporary file upload
    #[Vich\UploadableField(mapping: 'user_image', fileNameProperty: 'selfieWithId')]
    private ?File $selfieWithIdFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $incomeProof = null;

    // NOTE: This is not a mapped field of entity metadata, just a container to facilitate temporary file upload
    #[Vich\UploadableField(mapping: 'user_image', fileNameProperty: 'incomeProof')]
    private ?File $incomeProofFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ownershipProof = null;

    // NOTE: This is not a mapped field of entity metadata, just a container to facilitate temporary file upload
    #[Vich\UploadableField(mapping: 'user_image', fileNameProperty: 'ownershipProof')]
    private ?File $ownershipProofFile = null;

    #[ORM\Column]
    private ?bool $isEmailVerified = false;

    #[ORM\Column]
    private ?bool $isPhoneVerified = false;

    #[ORM\Column]
    private ?bool $isIdentityVerified = false;

    #[ORM\Column]
    private ?bool $isFinancialDocsVerified = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $phoneVerifiedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $identityVerifiedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $financialDocsVerifiedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailConfirmationToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $emailConfirmationTokenExpiresAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $emailVerificationAttempts = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastEmailVerificationRequestAt = null;

    #[ORM\Column]
    private ?bool $termsAccepted = false;

    #[ORM\Column]
    private ?bool $privacyAccepted = false;

    #[ORM\Column]
    private ?bool $marketingAccepted = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $termsAcceptedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $privacyAcceptedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $marketingAcceptedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'pending_verification';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastActiveAt = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $language = 'fr';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column]
    private ?int $profileViews = 0;

    #[ORM\Column(type: Types::JSON)]
    private array $metadata = [];

    #[ORM\OneToMany(targetEntity: House::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $houses;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $messages;

    public function __construct()
    {
        $this->houses = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->profileViews = 0;
        $this->emailVerificationAttempts = 0;
        $this->metadata = [];
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
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

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance of
     * UploadedFile can be injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of File object.
     */
    public function setProfilePictureFile(?File $profilePictureFile = null): void
    {
        $this->profilePictureFile = $profilePictureFile;

        if (null !== $profilePictureFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getProfilePictureFile(): ?File
    {
        return $this->profilePictureFile;
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

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance of
     * UploadedFile can be injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of File object.
     */
    public function setIdentityCardFile(?File $identityCardFile = null): void
    {
        $this->identityCardFile = $identityCardFile;

        if (null !== $identityCardFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getIdentityCardFile(): ?File
    {
        return $this->identityCardFile;
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

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance of
     * UploadedFile can be injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of File object.
     */
    public function setSelfieWithIdFile(?File $selfieWithIdFile = null): void
    {
        $this->selfieWithIdFile = $selfieWithIdFile;

        if (null !== $selfieWithIdFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getSelfieWithIdFile(): ?File
    {
        return $this->selfieWithIdFile;
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

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance of
     * UploadedFile can be injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of File object.
     */
    public function setIncomeProofFile(?File $incomeProofFile = null): void
    {
        $this->incomeProofFile = $incomeProofFile;

        if (null !== $incomeProofFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getIncomeProofFile(): ?File
    {
        return $this->incomeProofFile;
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

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance of
     * UploadedFile can be injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of File object.
     */
    public function setOwnershipProofFile(?File $ownershipProofFile = null): void
    {
        $this->ownershipProofFile = $ownershipProofFile;

        if (null !== $ownershipProofFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getOwnershipProofFile(): ?File
    {
        return $this->ownershipProofFile;
    }

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

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

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

    public function getDisplayName(): string
    {
        $fullName = trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
        return $fullName ?: 'Utilisateur';
    }

    public function __toString(): string
    {
        return $this->getEmail();
    }

    public function hasIdentityDocuments(): bool
    {
        return !empty($this->identityCard) && !empty($this->selfieWithId);
    }

    public function hasFinancialDocuments(): bool
    {
        return !empty($this->incomeProof) || !empty($this->ownershipProof);
    }
}