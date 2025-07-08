<?php

namespace App\Service\Message;

use App\Entity\Message;
use App\Entity\Property;
use App\Entity\User;
use App\Enum\MessageStatus;
use App\Enum\NotificationType;
use App\Repository\MessageRepository;
use App\Service\Email\NotificationEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Psr\Log\LoggerInterface;

class MessageService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepository $messageRepository,
        private MessageValidationService $validationService,
        private NotificationEmailService $notificationService,
        private LoggerInterface $logger
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
        if (!$sender->isEmailVerified()) {
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

        // Logging
        $this->logger->info('New message created', [
            'message_id' => $message->getId(),
            'sender_id' => $sender->getId(),
            'recipient_id' => $recipient->getId(),
            'property_id' => $property?->getId(),
            'needs_moderation' => $message->getNeedsModeration()
        ]);

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
            $message->setStatus(MessageStatus::APPROVED); // On garde APPROVED même si modifié
        } else {
            $message->setStatus(MessageStatus::APPROVED);
        }

        $message->setModeratedBy($moderator);
        $message->setModeratedAt(new \DateTimeImmutable());
        $message->setAdminValidated(true);
        $message->setValidatedBy($moderator);
        $message->setValidatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $this->logger->info('Message approved', [
            'message_id' => $message->getId(),
            'moderator_id' => $moderator->getId(),
            'was_edited' => $editedContent !== null
        ]);

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
        $message->setValidatedBy($moderator);
        $message->setValidatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $this->logger->info('Message rejected', [
            'message_id' => $message->getId(),
            'moderator_id' => $moderator->getId(),
            'reason' => $reason
        ]);

        // Notifier l'expéditeur du rejet
        $this->notificationService->createNotification(
            $message->getSender(),
            NotificationType::MESSAGE_REJECTED,
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
        return $this->messageRepository->countUnreadByUser($user);
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

        try {
            // Stats globales pour l'admin
            $total = $this->messageRepository->count([]);
            $pending = $this->messageRepository->countByStatus(MessageStatus::PENDING);
            $approved = $this->messageRepository->countByStatus(MessageStatus::APPROVED);
            $rejected = $this->messageRepository->countByStatus(MessageStatus::REJECTED);
            $withProperty = $this->messageRepository->countMessagesWithProperty();
            $unread = $this->countGlobalUnreadMessages();

            return [
                'total' => $total,
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected,
                'with_property' => $withProperty,
                'direct' => $total - $withProperty,
                'unread' => $unread,
                'recent_activity' => $this->getRecentMessageActivity()
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error calculating messaging stats: ' . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'with_property' => 0,
                'direct' => 0,
                'unread' => 0,
                'recent_activity' => []
            ];
        }
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

        if (!$sender->isEmailVerified()) {
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

    /**
     * Compter tous les messages non lus (global)
     */
    private function countGlobalUnreadMessages(): int
    {
        return $this->messageRepository->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.isRead = false')
            ->andWhere('m.status = :status')
            ->andWhere('m.deletedByRecipient = false')
            ->setParameter('status', MessageStatus::APPROVED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Obtenir l'activité récente des messages
     */
    private function getRecentMessageActivity(): array
    {
        return $this->messageRepository->createQueryBuilder('m')
            ->select('m.id, m.createdAt, m.status')
            ->addSelect('s.firstName as senderFirstName, s.lastName as senderLastName')
            ->addSelect('r.firstName as recipientFirstName, r.lastName as recipientLastName')
            ->leftJoin('m.sender', 's')
            ->leftJoin('m.recipient', 'r')
            ->where('m.createdAt >= :since')
            ->setParameter('since', new \DateTimeImmutable('-24 hours'))
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Valider et nettoyer un message avant envoi
     */
    public function validateAndCleanMessage(string $content): array
    {
        $validation = $this->validationService->validateMessage($content);
        $cleanContent = $this->validationService->sanitizeContent($content);

        return [
            'cleaned_content' => $cleanContent,
            'validation' => $validation,
            'suggestions' => $this->validationService->suggestCorrections($content)
        ];
    }

    /**
     * Analyser un message pour les statistiques de modération
     */
    public function analyzeMessageForModeration(Message $message): array
    {
        return $this->validationService->analyzeMessage($message);
    }
}
