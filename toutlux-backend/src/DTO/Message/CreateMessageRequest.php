<?php

namespace App\DTO\Message;

use Symfony\Component\Validator\Constraints as Assert;

class CreateMessageRequest
{
    #[Assert\NotBlank(message: 'Le contenu du message est requis')]
    #[Assert\Length(
        min: 10,
        minMessage: 'Le message doit contenir au moins {{ limit }} caractères',
        max: 2000,
        maxMessage: 'Le message ne peut pas dépasser {{ limit }} caractères'
    )]
    public string $content;

    #[Assert\Length(max: 255)]
    public ?string $subject = null;

    #[Assert\NotBlank(message: 'Le destinataire est requis')]
    #[Assert\Positive]
    public int $recipientId;

    #[Assert\Positive]
    public ?int $propertyId = null;

    #[Assert\Positive]
    public ?int $parentMessageId = null;
}
