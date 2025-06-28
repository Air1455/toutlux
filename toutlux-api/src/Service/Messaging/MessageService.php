<?php

namespace App\Service\Messaging;

use App\Entity\Message;
use App\Entity\User;
use App\Enum\MessageType;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Event\MessageCreatedEvent;

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
        MessageType $type = MessageType::USER_TO_ADMIN
    ): Message {
        $message = new Message();
        $message->setUser($user)
            ->setSubject($subject)
            ->setContent($content)
            ->setType($type->value);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Dispatch event pour déclencher l'envoi d'email
        $this->eventDispatcher->dispatch(
            new MessageCreatedEvent($message),
            'message.created'
        );

        return $message;
    }

    public function markAsRead(Message $message): void
    {
        $message->setIsRead(true);
        $this->entityManager->flush();
    }

    public function getUnreadCount(User $user): int
    {
        return $this->messageRepository->countUnreadByUser($user);
    }

    public function getUserMessages(User $user): array
    {
        return $this->messageRepository->findByUser($user);
    }

    public function getAdminMessages(): array
    {
        return $this->messageRepository->findAllForAdmin();
    }

    public function getUnreadAdminMessages(): array
    {
        return $this->messageRepository->findUnreadForAdmin();
    }
}
