<?php

namespace App\Entity;

use App\Repository\MessageRepository;
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
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/messages',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['message:list']],
            paginationEnabled: true,
            paginationItemsPerPage: 20
        ),
        new Get(
            uriTemplate: '/messages/{id}',
            security: "is_granted('ROLE_USER') and (object.getSender() == user or object.getRecipient() == user)",
            normalizationContext: ['groups' => ['message:read', 'message:detail']]
        ),
        new Post(
            uriTemplate: '/messages',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['message:read']],
            denormalizationContext: ['groups' => ['message:create']],
            validationContext: ['groups' => ['Default', 'message:create']]
        ),
        new Post(
            uriTemplate: '/messages/{id}/reply',
            security: "is_granted('ROLE_USER') and (object.getSender() == user or object.getRecipient() == user)",
            normalizationContext: ['groups' => ['message:read']],
            denormalizationContext: ['groups' => ['message:reply']],
            name: 'message_reply'
        ),
        new Put(
            uriTemplate: '/messages/{id}/read',
            security: "is_granted('ROLE_USER') and object.getRecipient() == user",
            denormalizationContext: ['groups' => []],
            name: 'message_mark_read'
        ),
        new Delete(
            uriTemplate: '/messages/{id}',
            security: "is_granted('ROLE_USER') and (object.getSender() == user or object.getRecipient() == user)"
        )
    ]
)]
#[ApiFilter(BooleanFilter::class, properties: ['isRead'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt'])]
class Message
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['message:list', 'message:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Subject is required')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'Subject must be at least {{ limit }} characters',
        maxMessage: 'Subject cannot exceed {{ limit }} characters'
    )]
    #[Groups(['message:list', 'message:read', 'message:create', 'message:reply'])]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Message content is required')]
    #[Assert\Length(
        min: 10,
        minMessage: 'Message must be at least {{ limit }} characters'
    )]
    #[Groups(['message:read', 'message:create', 'message:reply'])]
    private ?string $content = null;

    #[ORM\Column(length: 20)]
    #[Groups(['message:read'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['message:list', 'message:read'])]
    private bool $isRead = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['message:read'])]
    private ?string $moderatedContent = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['message:read'])]
    private ?\DateTimeInterface $moderatedAt = null;

    #[ORM\ManyToOne]
    private ?User $moderatedBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['message:list', 'message:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'sentMessages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['message:list', 'message:read'])]
    private ?User $sender = null;

    #[ORM\ManyToOne(inversedBy: 'receivedMessages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['message:list', 'message:read'])]
    private ?User $recipient = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[Groups(['message:read', 'message:create'])]
    private ?Property $property = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'replies')]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    #[Groups(['message:detail'])]
    private Collection $replies;

    public function __construct()
    {
        $this->replies = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?Uuid
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;
        return $this;
    }

    public function getModeratedContent(): ?string
    {
        return $this->moderatedContent;
    }

    public function setModeratedContent(?string $moderatedContent): static
    {
        $this->moderatedContent = $moderatedContent;
        return $this;
    }

    public function getModeratedAt(): ?\DateTimeInterface
    {
        return $this->moderatedAt;
    }

    public function setModeratedAt(?\DateTimeInterface $moderatedAt): static
    {
        $this->moderatedAt = $moderatedAt;
        return $this;
    }

    public function getModeratedBy(): ?User
    {
        return $this->moderatedBy;
    }

    public function setModeratedBy(?User $moderatedBy): static
    {
        $this->moderatedBy = $moderatedBy;
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

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;
        return $this;
    }

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient): static
    {
        $this->recipient = $recipient;
        return $this;
    }

    public function getProperty(): ?Property
    {
        return $this->property;
    }

    public function setProperty(?Property $property): static
    {
        $this->property = $property;
        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getReplies(): Collection
    {
        return $this->replies;
    }

    public function addReply(self $reply): static
    {
        if (!$this->replies->contains($reply)) {
            $this->replies->add($reply);
            $reply->setParent($this);
        }
        return $this;
    }

    public function removeReply(self $reply): static
    {
        if ($this->replies->removeElement($reply)) {
            if ($reply->getParent() === $this) {
                $reply->setParent(null);
            }
        }
        return $this;
    }

    #[Groups(['message:read'])]
    public function getDisplayContent(): string
    {
        if ($this->status === self::STATUS_APPROVED && $this->moderatedContent) {
            return $this->moderatedContent;
        }
        return $this->content;
    }

    public function needsModeration(): bool
    {
        // Messages between users about properties need moderation
        return $this->property !== null && $this->status === self::STATUS_PENDING;
    }

    public function approve(User $moderator, ?string $moderatedContent = null): void
    {
        $this->status = self::STATUS_APPROVED;
        $this->moderatedBy = $moderator;
        $this->moderatedAt = new \DateTime();

        if ($moderatedContent !== null) {
            $this->moderatedContent = $moderatedContent;
        }
    }

    public function reject(User $moderator): void
    {
        $this->status = self::STATUS_REJECTED;
        $this->moderatedBy = $moderator;
        $this->moderatedAt = new \DateTime();
    }
}
