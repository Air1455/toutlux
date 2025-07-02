<?php

namespace App\Entity;

use App\Repository\PropertyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PropertyRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/properties',
            normalizationContext: ['groups' => ['property:list']],
            paginationEnabled: true,
            paginationItemsPerPage: 20
        ),
        new Get(
            uriTemplate: '/properties/{id}',
            normalizationContext: ['groups' => ['property:read', 'property:detail']]
        ),
        new Post(
            uriTemplate: '/properties',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['property:read']],
            denormalizationContext: ['groups' => ['property:create']],
            validationContext: ['groups' => ['Default', 'property:create']]
        ),
        new Put(
            uriTemplate: '/properties/{id}',
            security: "is_granted('ROLE_USER') and object.getOwner() == user",
            normalizationContext: ['groups' => ['property:read']],
            denormalizationContext: ['groups' => ['property:update']]
        ),
        new Delete(
            uriTemplate: '/properties/{id}',
            security: "is_granted('ROLE_USER') and object.getOwner() == user or is_granted('ROLE_ADMIN')"
        )
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'city' => 'partial',
    'zipCode' => 'exact',
    'type' => 'exact',
    'status' => 'exact'
])]
#[ApiFilter(RangeFilter::class, properties: ['price', 'surface', 'rooms', 'bedrooms'])]
#[ApiFilter(OrderFilter::class, properties: ['price', 'surface', 'createdAt'])]
class Property
{
    public const TYPE_SALE = 'sale';
    public const TYPE_RENT = 'rent';

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_SOLD = 'sold';
    public const STATUS_RENTED = 'rented';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['property:list', 'property:read', 'message:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Title is required')]
    #[Assert\Length(
        min: 10,
        max: 255,
        minMessage: 'Title must be at least {{ limit }} characters',
        maxMessage: 'Title cannot exceed {{ limit }} characters'
    )]
    #[Groups(['property:list', 'property:read', 'property:create', 'property:update', 'message:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Description is required')]
    #[Assert\Length(
        min: 50,
        minMessage: 'Description must be at least {{ limit }} characters'
    )]
    #[Groups(['property:read', 'property:create', 'property:update'])]
    private ?string $description = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: 'Type is required')]
    #[Assert\Choice(choices: [self::TYPE_SALE, self::TYPE_RENT], message: 'Invalid property type')]
    #[Groups(['property:list', 'property:read', 'property:create', 'property:update'])]
    private ?string $type = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Price is required')]
    #[Assert\Positive(message: 'Price must be positive')]
    #[Groups(['property:list', 'property:read', 'property:create', 'property:update'])]
    private ?string $price = null;

    #[ORM\Column(type: Types::FLOAT)]
    #[Assert\NotBlank(message: 'Surface is required')]
    #[Assert\Positive(message: 'Surface must be positive')]
    #[Groups(['property:list', 'property:read', 'property:create', 'property:update'])]
    private ?float $surface = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank(message: 'Number of rooms is required')]
    #[Assert\Positive(message: 'Number of rooms must be positive')]
    #[Groups(['property:list', 'property:read', 'property:create', 'property:update'])]
    private ?int $rooms = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank(message: 'Number of bedrooms is required')]
    #[Assert\PositiveOrZero(message: 'Number of bedrooms cannot be negative')]
    #[Groups(['property:list', 'property:read', 'property:create', 'property:update'])]
    private ?int $bedrooms = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Address is required')]
    #[Groups(['property:read', 'property:create', 'property:update'])]
    private ?string $address = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'City is required')]
    #[Groups(['property:list', 'property:read', 'property:create', 'property:update'])]
    private ?string $city = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: 'Zip code is required')]
    #[Assert\Regex(pattern: '/^\d{5}$/', message: 'Invalid zip code format')]
    #[Groups(['property:list', 'property:read', 'property:create', 'property:update'])]
    private ?string $zipCode = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['property:read'])]
    private ?float $latitude = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['property:read'])]
    private ?float $longitude = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['property:read', 'property:create', 'property:update'])]
    private array $features = [];

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::STATUS_AVAILABLE, self::STATUS_SOLD, self::STATUS_RENTED])]
    #[Groups(['property:list', 'property:read'])]
    private string $status = self::STATUS_AVAILABLE;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['property:read'])]
    private int $viewCount = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['property:list', 'property:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['property:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'properties')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['property:detail'])]
    private ?User $owner = null;

    #[ORM\OneToMany(mappedBy: 'property', targetEntity: PropertyImage::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['property:list', 'property:read'])]
    private Collection $images;

    #[ORM\OneToMany(mappedBy: 'property', targetEntity: Message::class)]
    private Collection $messages;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->features = [];
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

    public function getId(): ?Uuid
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
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

    public function getSurface(): ?float
    {
        return $this->surface;
    }

    public function setSurface(float $surface): static
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

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(string $zipCode): static
    {
        $this->zipCode = $zipCode;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getFeatures(): array
    {
        return $this->features;
    }

    public function setFeatures(array $features): static
    {
        $this->features = $features;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function incrementViewCount(): static
    {
        $this->viewCount++;
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

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setProperty($this);
        }
        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getProperty() === $this) {
                $message->setProperty(null);
            }
        }
        return $this;
    }

    #[Groups(['property:list', 'property:read'])]
    public function getMainImageUrl(): ?string
    {
        foreach ($this->images as $image) {
            if ($image->isMain()) {
                return $image->getImageUrl();
            }
        }

        // If no main image, return first image
        if ($this->images->count() > 0) {
            return $this->images->first()->getImageUrl();
        }

        return null;
    }
}
