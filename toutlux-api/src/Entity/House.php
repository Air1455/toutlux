<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\HouseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HouseRepository::class)]
#[ApiResource]
class House
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstImage = null;

    #[ORM\Column(nullable: true)]
    private ?array $otherImages = null;

    #[ORM\Column]
    private ?int $price = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $bedrooms = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $bathrooms = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $address = null;

    #[ORM\Column(length: 255)]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    private ?string $country = null;

    #[ORM\Column(length: 50)]
    private ?string $shortDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $longDescription = null;

    #[ORM\Column]
    private array $location = [];

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $garages = null;

    #[ORM\Column(nullable: true)]
    private ?int $swimmingPools = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $floors = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $surface = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $yearOfConstruction = null;

    #[ORM\Column]
    private ?bool $isForRent = null;

    #[ORM\ManyToOne(inversedBy: 'houses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $currency = null;

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
        $this->otherImages = $otherImages;

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
        $this->currency = $currency;

        return $this;
    }
}
