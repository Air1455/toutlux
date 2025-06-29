<?php

namespace App\Service\Messaging;

use App\Entity\Message;
use App\Entity\User;
use App\Enum\MessageStatus;
use App\Enum\MessageType;
use App\Event\MessageCreatedEvent;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MessageService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepository $messageRepository,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function createMessage(
        User $user,
        string $subject,
        string $content,
        string|MessageType $type = MessageType::USER_TO_ADMIN
    ): Message {
        if (!$type instanceof MessageType) {
            $type = MessageType::from($type);
        }

        $message = new Message();
        $message->setUser($user)
            ->setSubject($subject)
            ->setContent($content)
            ->setType($type)
            ->setStatus(MessageStatus::UNREAD)
            ->setIsRead(false)
            ->setEmailSent(false);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Dispatch event pour notification email
        $this->eventDispatcher->dispatch(new MessageCreatedEvent($message), 'message.created');

        return $message;
    }

    public function getUserMessages(User $user): array
    {
        return $this->messageRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );
    }

    public function getUnreadCount(User $user): int
    {
        return $this->messageRepository->count([
            'user' => $user,
            'status' => MessageStatus::UNREAD
        ]);
    }

    public function markAsRead(Message $message): void
    {
        $message->setIsRead(true)
            ->setStatus(MessageStatus::READ);

        $this->entityManager->flush();
    }

    // ADMIN

    public function getAdminMessages(): array
    {
        return $this->messageRepository->findBy(
            ['type' => MessageType::USER_TO_ADMIN],
            ['createdAt' => 'DESC']
        );
    }

    public function getUnreadAdminMessages(): array
    {
        return $this->messageRepository->findBy(
            ['type' => MessageType::USER_TO_ADMIN, 'status' => MessageStatus::UNREAD],
            ['createdAt' => 'DESC']
        );
    }

    public function replyToUser(Message $originalMessage, string $replyContent): Message
    {
        $reply = $this->createMessage(
            $originalMessage->getUser(),
            'Re: ' . $originalMessage->getSubject(),
            $replyContent,
            MessageType::ADMIN_TO_USER
        );
        return $reply;
    }

    // Pour la pagination, la recherche avancée, le filtrage, etc., méthodes à ajouter ici.
}
