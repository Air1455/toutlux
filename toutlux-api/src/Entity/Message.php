<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['message:read']],
            security: "is_granted('ROLE_USER')"
        ),
        new Get(
            normalizationContext: ['groups' => ['message:read']],
            security: "is_granted('ROLE_USER') and (object.getUser() == user or is_granted('ROLE_ADMIN'))"
        ),
        new Post(
            denormalizationContext: ['groups' => ['message:write']],
            security: "is_granted('ROLE_USER')"
        ),
        new Put(
            denormalizationContext: ['groups' => ['message:update']],
            security: "is_granted('ROLE_USER') and (object.getUser() == user or is_granted('ROLE_ADMIN'))"
        ),
    ]
)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['message:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le sujet est obligatoire')]
    #[Groups(['message:read', 'message:write'])]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le contenu est obligatoire')]
    #[Groups(['message:read', 'message:write'])]
    private ?string $content = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['system', 'user_to_admin', 'admin_to_user'])]
    #[Groups(['message:read'])]
    private ?string $type = 'user_to_admin';

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['unread', 'read', 'archived'])]
    #[Groups(['message:read', 'message:update'])]
    private ?string $status = 'unread';

    #[ORM\Column]
    #[Groups(['message:read'])]
    private ?bool $isRead = false;

    #[ORM\Column]
    #[Groups(['message:read'])]
    private ?bool $emailSent = false;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[Groups(['message:read'])]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['message:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['message:read'])]
    private ?\DateTimeImmutable $readAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        if ($this->isRead && !$this->readAt) {
            $this->readAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function isRead(): ?bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;
        if ($isRead && !$this->readAt) {
            $this->readAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function isEmailSent(): ?bool
    {
        return $this->emailSent;
    }

    public function setEmailSent(bool $emailSent): static
    {
        $this->emailSent = $emailSent;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }
}
