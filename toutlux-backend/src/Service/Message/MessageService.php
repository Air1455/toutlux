<?php

namespace App\Service\Message;

use App\Entity\Message;
use App\Entity\Property;
use App\Entity\User;
use App\Enum\MessageStatus;
use App\Repository\MessageRepository;
use App\Service\Email\NotificationEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class MessageService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepository $messageRepository,
        private MessageValidationService $validationService,
        private NotificationEmailService $notificationService
    ) {}

    /**
     * Créer un nouveau message
     */
    public function createMessage(
        User $sender,
        User $recipient,
        string $content,
        ?Property $property = null,
        ?Message $parentMessage = null
    ): Message {
        // Vérifier les permissions
        if (!$sender->isVerified()) {
            throw new AccessDeniedException('Vous devez vérifier votre email pour envoyer des messages.');
        }

        if ($sender->getId() === $recipient->getId()) {
            throw new \InvalidArgumentException('Vous ne pouvez pas vous envoyer un message à vous-même.');
        }

        $message = new Message();
        $message->setSender($sender);
        $message->setRecipient($recipient);
        $message->setContent($content);
        $message->setProperty($property);
        $message->setParentMessage($parentMessage);
        $message->setIsRead(false);

        // Si le message concerne une propriété, il doit être validé par l'admin
        if ($property !== null) {
            $message->setStatus(MessageStatus::PENDING);
            $message->setNeedsModeration(true);
        } else {
            $message->setStatus(MessageStatus::APPROVED);
            $message->setNeedsModeration(false);
            $message->setAdminValidated(true);
        }

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Si le message nécessite une modération
        if ($message->getNeedsModeration()) {
            $this->notificationService->notifyAdminMessageToModerate($message);
        } else {
            // Sinon, notifier directement le destinataire
            $this->notificationService->notifyNewMessage($message);
        }

        return $message;
    }

    /**
     * Approuver un message (admin)
     */
    public function approveMessage(Message $message, User $moderator, ?string $editedContent = null): void
    {
        if ($message->getStatus() !== MessageStatus::PENDING) {
            throw new \LogicException('Ce message n\'est pas en attente de modération.');
        }

        // Si le contenu a été édité
        if ($editedContent !== null && $editedContent !== $message->getContent()) {
            $message->setOriginalContent($message->getContent());
            $message->setContent($editedContent);
            $message->setEditedByModerator(true);
            $message->setStatus(MessageStatus::MODIFIED);
        } else {
            $message->setStatus(MessageStatus::APPROVED);
        }

        $message->setModeratedBy($moderator);
        $message->setModeratedAt(new \DateTimeImmutable());
        $message->setAdminValidated(true);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Notifier le destinataire
        $this->notificationService->notifyNewMessage($message);
    }

    /**
     * Rejeter un message (admin)
     */
    public function rejectMessage(Message $message, User $moderator, string $reason): void
    {
        if ($message->getStatus() !== MessageStatus::PENDING) {
            throw new \LogicException('Ce message n\'est pas en attente de modération.');
        }

        $message->setStatus(MessageStatus::REJECTED);
        $message->setModeratedBy($moderator);
        $message->setModeratedAt(new \DateTimeImmutable());
        $message->setModerationReason($reason);
        $message->setAdminValidated(false);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Notifier l'expéditeur du rejet
        $this->notificationService->createNotification(
            $message->getSender(),
            'message_rejected',
            'Message rejeté',
            sprintf('Votre message à %s a été rejeté. Raison : %s',
                $message->getRecipient()->getFullName(),
                $reason
            ),
            ['message_id' => $message->getId()]
        );
    }

    /**
     * Marquer un message comme lu
     */
    public function markAsRead(Message $message, User $user): void
    {
        if ($message->getRecipient()->getId() !== $user->getId()) {
            throw new AccessDeniedException('Vous ne pouvez pas marquer ce message comme lu.');
        }

        if (!$message->isRead()) {
            $message->setIsRead(true);
            $message->setReadAt(new \DateTimeImmutable());
            $this->entityManager->persist($message);
            $this->entityManager->flush();
        }
    }

    /**
     * Marquer plusieurs messages comme lus
     */
    public function markMultipleAsRead(array $messageIds, User $user): int
    {
        $messages = $this->messageRepository->findBy([
            'id' => $messageIds,
            'recipient' => $user,
            'isRead' => false
        ]);

        $count = 0;
        foreach ($messages as $message) {
            $message->setIsRead(true);
            $message->setReadAt(new \DateTimeImmutable());
            $this->entityManager->persist($message);
            $count++;
        }

        if ($count > 0) {
            $this->entityManager->flush();
        }

        return $count;
    }

    /**
     * Supprimer un message (soft delete)
     */
    public function deleteMessage(Message $message, User $user): void
    {
        if ($message->getSender()->getId() === $user->getId()) {
            $message->setDeletedBySender(true);
        } elseif ($message->getRecipient()->getId() === $user->getId()) {
            $message->setDeletedByRecipient(true);
        } else {
            throw new AccessDeniedException('Vous ne pouvez pas supprimer ce message.');
        }

        $this->entityManager->persist($message);
        $this->entityManager->flush();
    }

    /**
     * Obtenir les conversations d'un utilisateur
     */
    public function getUserConversations(User $user, int $limit = 20, int $offset = 0): array
    {
        return $this->messageRepository->findUserConversations($user, $limit, $offset);
    }

    /**
     * Obtenir une conversation entre deux utilisateurs
     */
    public function getConversation(User $user1, User $user2, ?Property $property = null): array
    {
        $messages = $this->messageRepository->findConversationBetween($user1, $user2, $property);

        // Marquer comme lus les messages reçus
        foreach ($messages as $message) {
            if ($message->getRecipient()->getId() === $user1->getId() && !$message->isRead()) {
                $this->markAsRead($message, $user1);
            }
        }

        return $messages;
    }

    /**
     * Compter les messages non lus
     */
    public function countUnreadMessages(User $user): int
    {
        return $this->messageRepository->count([
            'recipient' => $user,
            'isRead' => false,
            'status' => 'sent',
            'deletedByRecipient' => false
        ]);
    }

    /**
     * Rechercher des messages
     */
    public function searchMessages(User $user, string $query, array $filters = []): array
    {
        return $this->messageRepository->searchUserMessages($user, $query, $filters);
    }

    /**
     * Obtenir les messages en attente de modération
     */
    public function getPendingModerationMessages(int $limit = null): array
    {
        return $this->messageRepository->findByStatus(MessageStatus::PENDING, $limit);
    }

    /**
     * Obtenir les statistiques de messagerie
     */
    public function getMessagingStats(User $user = null): array
    {
        if ($user) {
            return $this->messageRepository->getUserMessageStats($user);
        }

        // Stats globales pour l'admin
        return [
            'total' => $this->messageRepository->count([]),
            'pending' => $this->messageRepository->countByStatus(MessageStatus::PENDING),
            'approved' => $this->messageRepository->countByStatus(MessageStatus::APPROVED),
            'rejected' => $this->messageRepository->countByStatus(MessageStatus::REJECTED),
            'with_property' => $this->messageRepository->countMessagesWithProperty()
        ];
    }

    /**
     * Archiver une conversation
     */
    public function archiveConversation(User $user, User $otherUser): void
    {
        $messages = $this->messageRepository->findConversationBetween($user, $otherUser);

        foreach ($messages as $message) {
            if ($message->getSender()->getId() === $user->getId()) {
                $message->setArchivedBySender(true);
            }
            if ($message->getRecipient()->getId() === $user->getId()) {
                $message->setArchivedByRecipient(true);
            }
            $this->entityManager->persist($message);
        }

        $this->entityManager->flush();
    }

    /**
     * Bloquer un utilisateur
     */
    public function blockUser(User $blocker, User $blocked): void
    {
        // TODO: Implémenter la logique de blocage
        // Cela nécessiterait une table de blocage ou un champ dans User
    }

    /**
     * Vérifier si un message peut être envoyé
     */
    public function canSendMessage(User $sender, User $recipient): array
    {
        $errors = [];

        if (!$sender->isVerified()) {
            $errors[] = 'Votre email doit être vérifié pour envoyer des messages.';
        }

        if ($sender->getId() === $recipient->getId()) {
            $errors[] = 'Vous ne pouvez pas vous envoyer un message.';
        }

        // TODO: Vérifier si l'utilisateur est bloqué

        return [
            'can_send' => empty($errors),
            'errors' => $errors
        ];
    }
}
