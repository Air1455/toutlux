<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\HouseRepository;
use App\State\HouseStateProcessor;
use App\Validator\CurrencyCode;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HouseRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['house:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['house:read']]
        ),
        new Post(
            denormalizationContext: ['groups' => ['house:write']],
            processor: HouseStateProcessor::class,
            security: "is_granted('ROLE_USER')"
        ),
        new Put(
            denormalizationContext: ['groups' => ['house:write']],
            processor: HouseStateProcessor::class,
            security: "is_granted('ROLE_USER') and object.getUser() == user"
        ),
        new Delete(
            security: "is_granted('ROLE_USER') and object.getUser() == user"
        )
    ]
)]
class House
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['house:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['house:read', 'house:write'])]
    private ?string $firstImage = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['house:read', 'house:write'])]
    private ?array $otherImages = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le prix est obligatoire')]
    #[Assert\Positive(message: 'Le prix doit être positif')]
    #[Groups(['house:read', 'house:write'])]
    private ?int $price = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Positive(message: 'Le nombre de chambres doit être positif')]
    #[Groups(['house:read', 'house:write'])]
    private ?int $bedrooms = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Positive(message: 'Le nombre de salles de bain doit être positif')]
    #[Groups(['house:read', 'house:write'])]
    private ?int $bathrooms = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'L\'adresse est obligatoire')]
    #[Groups(['house:read', 'house:write'])]
    private ?string $address = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La ville est obligatoire')]
    #[Groups(['house:read', 'house:write'])]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le pays est obligatoire')]
    #[Groups(['house:read', 'house:write'])]
    private ?string $country = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'La description courte est obligatoire')]
    #[Assert\Length(
        min: 10,
        max: 100,
        minMessage: 'La description courte doit faire au moins {{ limit }} caractères',
        maxMessage: 'La description courte ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Groups(['house:read', 'house:write'])]
    private ?string $shortDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'La description longue ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Groups(['house:read', 'house:write'])]
    private ?string $longDescription = null;

    #[ORM\Column]
    #[Groups(['house:read', 'house:write'])]
    private array $location = [];

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le nombre de garages doit être positif ou zéro')]
    #[Groups(['house:read', 'house:write'])]
    private ?int $garages = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le nombre de piscines doit être positif ou zéro')]
    #[Groups(['house:read', 'house:write'])]
    private ?int $swimmingPools = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Positive(message: 'Le nombre d\'étages doit être positif')]
    #[Groups(['house:read', 'house:write'])]
    private ?int $floors = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['house:read', 'house:write'])]
    private ?string $surface = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le type de propriété est obligatoire')]
    #[Assert\Choice(
        choices: ['apartment', 'house', 'villa', 'studio', 'loft', 'townhouse', 'duplex', 'penthouse'],
        message: 'Type de propriété invalide'
    )]
    #[Groups(['house:read', 'house:write'])]
    private ?string $type = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(
        min: 1800,
        max: 2030,
        notInRangeMessage: 'L\'année de construction doit être entre {{ min }} et {{ max }}'
    )]
    #[Groups(['house:read', 'house:write'])]
    private ?int $yearOfConstruction = null;

    #[ORM\Column]
    #[Groups(['house:read', 'house:write'])]
    private ?bool $isForRent = null;

    #[ORM\ManyToOne(inversedBy: 'houses')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['house:read'])]
    private ?User $user = null;

    #[ORM\Column(length: 3)]
    #[Assert\NotBlank(message: 'La devise est obligatoire')]
    #[CurrencyCode]
    #[Groups(['house:read', 'house:write'])]
    private ?string $currency = 'XOF';

    #[ORM\Column(length: 20)]
    #[Groups(['house:read'])]
    #[Assert\Choice(
        choices: ['active', 'suspended', 'pending', 'rejected'],
        message: 'Statut invalide'
    )]
    private ?string $status = 'active';

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['house:read'])]
    private array $metadata = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['house:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['house:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->metadata = [];
        $this->status = 'active';
        $this->otherImages = [];
        $this->location = ['lat' => 0, 'lng' => 0];
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters et setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstImage(): ?string
    {
        return $this->firstImage;
    }

    public function setFirstImage(?string $firstImage): static
    {
        $this->firstImage = $firstImage;
        return $this;
    }

    public function getOtherImages(): ?array
    {
        return $this->otherImages;
    }

    public function setOtherImages(?array $otherImages): static
    {
        $this->otherImages = $otherImages ?? [];
        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getBedrooms(): ?int
    {
        return $this->bedrooms;
    }

    public function setBedrooms(?int $bedrooms): static
    {
        $this->bedrooms = $bedrooms;
        return $this;
    }

    public function getBathrooms(): ?int
    {
        return $this->bathrooms;
    }

    public function setBathrooms(?int $bathrooms): static
    {
        $this->bathrooms = $bathrooms;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;
        return $this;
    }

    public function getLongDescription(): ?string
    {
        return $this->longDescription;
    }

    public function setLongDescription(?string $longDescription): static
    {
        $this->longDescription = $longDescription;
        return $this;
    }

    public function getLocation(): array
    {
        return $this->location;
    }

    public function setLocation(array $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getGarages(): ?int
    {
        return $this->garages;
    }

    public function setGarages(?int $garages): static
    {
        $this->garages = $garages;
        return $this;
    }

    public function getSwimmingPools(): ?int
    {
        return $this->swimmingPools;
    }

    public function setSwimmingPools(?int $swimmingPools): static
    {
        $this->swimmingPools = $swimmingPools;
        return $this;
    }

    public function getFloors(): ?int
    {
        return $this->floors;
    }

    public function setFloors(?int $floors): static
    {
        $this->floors = $floors;
        return $this;
    }

    public function getSurface(): ?string
    {
        return $this->surface;
    }

    public function setSurface(?string $surface): static
    {
        $this->surface = $surface;
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

    public function getYearOfConstruction(): ?int
    {
        return $this->yearOfConstruction;
    }

    public function setYearOfConstruction(?int $yearOfConstruction): static
    {
        $this->yearOfConstruction = $yearOfConstruction;
        return $this;
    }

    public function isForRent(): ?bool
    {
        return $this->isForRent;
    }

    public function setIsForRent(bool $isForRent): static
    {
        $this->isForRent = $isForRent;
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

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = strtoupper(trim($currency));
        return $this;
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

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function addMetadata(string $key, mixed $value): static
    {
        $this->metadata[$key] = $value;
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
}
