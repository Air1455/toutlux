<?php

namespace App\DTO\Message;

class MessageResponse
{
    public int $id;
    public string $content;
    public ?string $subject;
    public array $sender;
    public array $recipient;
    public ?array $property;
    public bool $isRead;
    public ?string $readAt;
    public string $status;
    public string $createdAt;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->content = $data['content'];
        $this->subject = $data['subject'] ?? null;
        $this->sender = $data['sender'];
        $this->recipient = $data['recipient'];
        $this->property = $data['property'] ?? null;
        $this->isRead = $data['isRead'] ?? false;
        $this->readAt = $data['readAt'] ?? null;
        $this->status = $data['status'] ?? 'sent';
        $this->createdAt = $data['createdAt'];
    }
}
