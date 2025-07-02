<?php

namespace App\Entity;

use App\Repository\UserProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserProfileRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/profile',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['profile:read']],
            provider: 'App\State\UserProfileProvider'
        ),
        new Put(
            uriTemplate: '/profile',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['profile:read']],
            denormalizationContext: ['groups' => ['profile:write']],
            processor: 'App\State\UserProfileProcessor'
        ),
        new Post(
            uriTemplate: '/profile/avatar',
            inputFormats: ['multipart' => ['multipart/form-data']],
            security: "is_granted('ROLE_USER')",
            denormalizationContext: ['groups' => ['profile:avatar']],
            processor: 'App\State\UserProfileAvatarProcessor'
        )
    ]
)]
class UserProfile
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['profile:read', 'user:read'])]
    private ?Uuid $id = null;

    #[ORM\OneToOne(inversedBy: 'profile', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: 'First name is required', groups: ['profile:personal'])]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'First name must be at least {{ limit }} characters',
        maxMessage: 'First name cannot exceed {{ limit }} characters'
    )]
    #[Groups(['profile:read', 'profile:write', 'user:read', 'message:read', 'property:read'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: 'Last name is required', groups: ['profile:personal'])]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Last name must be at least {{ limit }} characters',
        maxMessage: 'Last name cannot exceed {{ limit }} characters'
    )]
    #[Groups(['profile:read', 'profile:write', 'user:read', 'message:read', 'property:read'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\NotBlank(message: 'Phone number is required', groups: ['profile:personal'])]
    #[Assert\Regex(
        pattern: '/^(\+33|0)[1-9](\d{2}){4}$/',
        message: 'Invalid French phone number format'
    )]
    #[Groups(['profile:read', 'profile:write'])]
    private ?string $phoneNumber = null;

    #[Vich\UploadableField(mapping: 'profile_images', fileNameProperty: 'profilePictureName')]
    #[Assert\File(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
        mimeTypesMessage: 'Please upload a valid image (JPEG, PNG, or WebP)'
    )]
    #[Groups(['profile:avatar'])]
    private ?File $profilePictureFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['profile:read'])]
    private ?string $profilePictureName = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['profile:read', 'user:read'])]
    private bool $personalInfoValidated = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['profile:read', 'user:read'])]
    private bool $identityValidated = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['profile:read', 'user:read'])]
    private bool $financialValidated = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['profile:read', 'user:read'])]
    private bool $termsAccepted = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['profile:read'])]
    private ?\DateTimeInterface $termsAcceptedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['profile:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
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

    public function getProfilePictureFile(): ?File
    {
        return $this->profilePictureFile;
    }

    public function setProfilePictureFile(?File $profilePictureFile = null): static
    {
        $this->profilePictureFile = $profilePictureFile;

        if (null !== $profilePictureFile) {
            $this->updatedAt = new \DateTime();
        }

        return $this;
    }

    public function getProfilePictureName(): ?string
    {
        return $this->profilePictureName;
    }

    public function setProfilePictureName(?string $profilePictureName): static
    {
        $this->profilePictureName = $profilePictureName;
        return $this;
    }

    #[Groups(['profile:read'])]
    public function getProfilePictureUrl(): ?string
    {
        if ($this->profilePictureName) {
            return '/uploads/profiles/' . $this->profilePictureName;
        }
        return null;
    }

    public function isPersonalInfoValidated(): bool
    {
        return $this->personalInfoValidated;
    }

    public function setPersonalInfoValidated(bool $personalInfoValidated): static
    {
        $this->personalInfoValidated = $personalInfoValidated;
        return $this;
    }

    public function isIdentityValidated(): bool
    {
        return $this->identityValidated;
    }

    public function setIdentityValidated(bool $identityValidated): static
    {
        $this->identityValidated = $identityValidated;
        return $this;
    }

    public function isFinancialValidated(): bool
    {
        return $this->financialValidated;
    }

    public function setFinancialValidated(bool $financialValidated): static
    {
        $this->financialValidated = $financialValidated;
        return $this;
    }

    public function isTermsAccepted(): bool
    {
        return $this->termsAccepted;
    }

    public function setTermsAccepted(bool $termsAccepted): static
    {
        $this->termsAccepted = $termsAccepted;

        if ($termsAccepted && !$this->termsAcceptedAt) {
            $this->termsAcceptedAt = new \DateTime();
        }

        return $this;
    }

    public function getTermsAcceptedAt(): ?\DateTimeInterface
    {
        return $this->termsAcceptedAt;
    }

    public function setTermsAcceptedAt(?\DateTimeInterface $termsAcceptedAt): static
    {
        $this->termsAcceptedAt = $termsAcceptedAt;
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

    #[Groups(['profile:read'])]
    public function getCompletionPercentage(): int
    {
        $completed = 0;

        if ($this->personalInfoValidated) $completed++;
        if ($this->identityValidated) $completed++;
        if ($this->financialValidated) $completed++;
        if ($this->termsAccepted) $completed++;

        return ($completed / 4) * 100;
    }

    public function getFullName(): ?string
    {
        if ($this->firstName && $this->lastName) {
            return $this->firstName . ' ' . $this->lastName;
        }
        return null;
    }

    public function __toString(): string
    {
        return $this->getFullName() ?? '';
    }

    public function isPersonalInfoComplete(): bool
    {
        return !empty($this->firstName) &&
            !empty($this->lastName) &&
            !empty($this->phoneNumber) &&
            !empty($this->profilePictureName);
    }
}
