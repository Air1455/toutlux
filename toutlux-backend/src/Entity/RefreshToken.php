<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_token')]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['refresh_token:read']]),
        new Post(denormalizationContext: ['groups' => ['refresh_token:write']]),
        new Put(denormalizationContext: ['groups' => ['refresh_token:write']]),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['refresh_token:read']],
    denormalizationContext: ['groups' => ['refresh_token:write']]
)]
class RefreshToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['refresh_token:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['refresh_token:read', 'refresh_token:write'])]
    private ?string $token = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['refresh_token:read', 'refresh_token:write'])]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['refresh_token:read', 'refresh_token:write'])]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['refresh_token:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Groups(['refresh_token:read', 'refresh_token:write'])]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['refresh_token:read', 'refresh_token:write'])]
    private ?string $userAgent = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->token = bin2hex(random_bytes(32));
        // Expire dans 30 jours
        $this->expiresAt = new \DateTimeImmutable('+30 days');
    }

    public function getId(): ?int { return $this->id; }
    public function getToken(): ?string { return $this->token; }
    public function setToken(string $token): self { $this->token = $token; return $this; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
    public function getExpiresAt(): ?\DateTimeImmutable { return $this->expiresAt; }
    public function setExpiresAt(\DateTimeImmutable $expiresAt): self { $this->expiresAt = $expiresAt; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getIpAddress(): ?string { return $this->ipAddress; }
    public function setIpAddress(?string $ipAddress): self { $this->ipAddress = $ipAddress; return $this; }
    public function getUserAgent(): ?string { return $this->userAgent; }
    public function setUserAgent(?string $userAgent): self { $this->userAgent = $userAgent; return $this; }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }
}
