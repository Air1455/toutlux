<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use App\Repository\PropertyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PropertyRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('PUBLIC_ACCESS')",
            normalizationContext: ['groups' => ['property:list']]
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            denormalizationContext: ['groups' => ['property:write']],
            normalizationContext: ['groups' => ['property:read']],
            validationContext: ['groups' => ['Default', 'property:create']]
        ),
        new Get(
            security: "is_granted('PUBLIC_ACCESS')",
            normalizationContext: ['groups' => ['property:read', 'property:detail']]
        ),
        new Put(
            security: "is_granted('ROLE_USER') and object.getOwner() == user",
            denormalizationContext: ['groups' => ['property:update']],
            normalizationContext: ['groups' => ['property:read']]
        ),
        new Patch(
            security: "is_granted('ROLE_USER') and object.getOwner() == user",
            denormalizationContext: ['groups' => ['property:update']],
            normalizationContext: ['groups' => ['property:read']]
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_USER') and object.getOwner() == user)"
        )
    ],
    normalizationContext: ['groups' => ['property:read']],
    denormalizationContext: ['groups' => ['property:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 20
)]
#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'partial',
    'description' => 'partial',
    'city' => 'partial',
    'postalCode' => 'exact',
    'type' => 'exact'
])]
#[ApiFilter(RangeFilter::class, properties: ['price', 'surface', 'rooms', 'bedrooms', 'bathrooms'])]
#[ApiFilter(BooleanFilter::class, properties: ['available', 'verified'])]
#[ApiFilter(OrderFilter::class, properties: ['price', 'surface', 'createdAt'], arguments: ['orderParameterName' => 'order'])]
class Property
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['property:read', 'message:read', 'property:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['property:create'])]
    #[Assert\Length(max: 255)]
    #[Groups(['property:read', 'property:write', 'property:update', 'property:list'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(groups: ['property:create'])]
    #[Assert\Length(min: 50, minMessage: 'La description doit contenir au moins 50 caractères')]
    #[Groups(['property:read', 'property:write', 'property:update', 'property:detail'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(groups: ['property:create'])]
    #[Assert\Positive(message: 'Le prix doit être positif')]
    #[Groups(['property:read', 'property:write', 'property:update', 'property:list'])]
    private ?string $price = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(groups: ['property:create'])]
    #[Assert\Choice(choices: ['sale', 'rent'], message: 'Le type doit être "sale" ou "rent"')]
    #[Groups(['property:read', 'property:write', 'property:update', 'property:list'])]
    private ?string $type = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank(groups: ['property:create'])]
    #[Assert\Positive(message: 'La surface doit être positive')]
    #[Groups(['property:read', 'property:write', 'property:update', 'property:list'])]
    private ?int $surface = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\PositiveOrZero(message: 'Le nombre de pièces doit être positif ou zéro')]
    #[Groups(['property:read', 'property:write', 'property:update', 'property:list'])]
    private ?int $rooms = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\PositiveOrZero(message: 'Le nombre de chambres doit être positif ou zéro')]
    #[Groups(['property:read', 'property:write', 'property:update'])]
    private ?int $bedrooms = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\PositiveOrZero(message: 'Le nombre de salles de bain doit être positif ou zéro')]
    #[Groups(['property:read', 'property:write', 'property:update'])]
    private ?int $bathrooms = null;

    // Location
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['property:create'])]
    #[Groups(['property:read', 'property:write', 'property:update', 'property:list'])]
    private ?string $address = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(groups: ['property:create'])]
    #[Groups(['property:read', 'property:write', 'property:update', 'property:list'])]
    private ?string $city = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(groups: ['property:create'])]
    #[Assert\Regex(pattern: '/^[0-9]{5}$/', message: 'Code postal invalide')]
    #[Groups(['property:read', 'property:write', 'property:update'])]
    private ?string $postalCode = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    #[Groups(['property:read', 'property:write', 'property:update'])]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    #[Groups(['property:read', 'property:write', 'property:update'])]
    private ?string $longitude = null;

    // Features
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['property:read', 'property:write', 'property:update', 'property:detail'])]
    private ?array $features = [];

    // Status
    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['property:read', 'property:write', 'property:update'])]
    private bool $available = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['property:read'])]
    private bool $verified = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['property:read'])]
    private bool $featured = false;

    // Relations
    #[ORM\ManyToOne(inversedBy: 'properties')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['property:read', 'property:detail'])]
    private ?User $owner = null;

    #[ORM\OneToMany(mappedBy: 'property', targetEntity: PropertyImage::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['property:read', 'property:detail'])]
    #[Assert\Valid]
    private Collection $images;

    #[ORM\OneToMany(mappedBy: 'property', targetEntity: Message::class)]
    private Collection $messages;

    // SEO
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['property:read', 'property:write', 'property:update'])]
    private ?string $metaTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['property:read', 'property:write', 'property:update'])]
    private ?string $metaDescription = null;

    // Statistics
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['property:read'])]
    private int $viewCount = 0;

    // Timestamps
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['property:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->features = [];
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
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

    public function getSurface(): ?int
    {
        return $this->surface;
    }

    public function setSurface(int $surface): static
    {
        $this->surface = $surface;
        return $this;
    }

    public function getRooms(): ?int
    {
        return $this->rooms;
    }

    public function setRooms(int $rooms): static
    {
        $this->rooms = $rooms;
        return $this;
    }

    public function getBedrooms(): ?int
    {
        return $this->bedrooms;
    }

    public function setBedrooms(int $bedrooms): static
    {
        $this->bedrooms = $bedrooms;
        return $this;
    }

    public function getBathrooms(): ?int
    {
        return $this->bathrooms;
    }

    public function setBathrooms(int $bathrooms): static
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

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getFeatures(): array
    {
        return $this->features ?? [];
    }

    public function setFeatures(?array $features): static
    {
        $this->features = $features;
        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function setAvailable(bool $available): static
    {
        $this->available = $available;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): static
    {
        $this->verified = $verified;
        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): static
    {
        $this->featured = $featured;
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * @return Collection<int, PropertyImage>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(PropertyImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setProperty($this);
        }

        return $this;
    }

    public function removeImage(PropertyImage $image): static
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getProperty() === $this) {
                $image->setProperty(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): static
    {
        $this->metaTitle = $metaTitle;
        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;
        return $this;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function setViewCount(int $viewCount): static
    {
        $this->viewCount = $viewCount;
        return $this;
    }

    public function incrementViewCount(): static
    {
        $this->viewCount++;
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
     * Get the main image of the property
     */
    public function getMainImage(): ?PropertyImage
    {
        foreach ($this->images as $image) {
            if ($image->isMain()) {
                return $image;
            }
        }

        return $this->images->first() ?: null;
    }

    /**
     * Get full address
     */
    public function getFullAddress(): string
    {
        return sprintf('%s, %s %s', $this->address, $this->postalCode, $this->city);
    }

    /**
     * Get price formatted
     */
    public function getFormattedPrice(): string
    {
        $formatter = new \NumberFormatter('fr_FR', \NumberFormatter::CURRENCY);
        return $formatter->formatCurrency((float) $this->price, 'EUR');
    }

    /**
     * Get price per square meter
     */
    public function getPricePerSquareMeter(): ?float
    {
        if ($this->surface > 0) {
            return (float) $this->price / $this->surface;
        }

        return null;
    }
}
