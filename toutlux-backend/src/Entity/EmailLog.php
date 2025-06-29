<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\EmailLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EmailLogRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['email_log:read']]),
        new Post(denormalizationContext: ['groups' => ['email_log:write']]),
        new Put(denormalizationContext: ['groups' => ['email_log:write']]),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['email_log:read']],
    denormalizationContext: ['groups' => ['email_log:write']]
)]
class EmailLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['email_log:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['email_log:read', 'email_log:write'])]
    private ?string $toEmail = null;

    #[ORM\Column(length: 255)]
    #[Groups(['email_log:read', 'email_log:write'])]
    private ?string $subject = null;

    #[ORM\Column(length: 50)]
    #[Groups(['email_log:read', 'email_log:write'])]
    private ?string $template = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['email_log:read', 'email_log:write'])]
    private array $templateData = [];

    #[ORM\Column(length: 20)]
    #[Groups(['email_log:read', 'email_log:write'])]
    private ?string $status = 'pending';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['email_log:read', 'email_log:write'])]
    private ?string $errorMessage = null;

    #[ORM\ManyToOne]
    #[Groups(['email_log:read', 'email_log:write'])]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[Groups(['email_log:read', 'email_log:write'])]
    private ?Message $message = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['email_log:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['email_log:read'])]
    private ?\DateTimeImmutable $sentAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToEmail(): ?string
    {
        return $this->toEmail;
    }

    public function setToEmail(string $toEmail): static
    {
        $this->toEmail = $toEmail;
        return $this;
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

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(string $template): static
    {
        $this->template = $template;
        return $this;
    }

    public function getTemplateData(): array
    {
        return $this->templateData;
    }

    public function setTemplateData(array $templateData): static
    {
        $this->templateData = $templateData;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        if ($status === 'sent' && !$this->sentAt) {
            $this->sentAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;
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

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }
}
