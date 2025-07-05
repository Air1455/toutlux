<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Enum\NotificationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use App\Message\SendNotificationMessage;
use Psr\Log\LoggerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger
    ) {}

    /**
     * Créer une notification in-app pour un utilisateur
     */
    public function createNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        bool $sendEmail = true
    ): Notification {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType($type);
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setData($data);
        $notification->setIsRead(false);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        // Envoyer une notification push/email de manière asynchrone
        if ($sendEmail) {
            $this->messageBus->dispatch(
                new SendNotificationMessage($notification->getId()),
                [new DelayStamp(1000)] // Délai de 1 seconde
            );
        }

        $this->logger->info('Notification created', [
            'user' => $user->getId(),
            'type' => $type,
            'title' => $title
        ]);

        return $notification;
    }


    /**
     * Créer une notification pour plusieurs utilisateurs
     */
    public function createBulkNotifications(
        array $users,
        NotificationType $type,
        string $title,
        string $message,
        array $data = []
    ): array {
        $notifications = [];

        foreach ($users as $user) {
            $notifications[] = $this->createNotification(
                $user,
                $type,
                $title,
                $message,
                $data,
                false // On n'envoie pas d'email pour les notifications en masse
            );
        }

        return $notifications;
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Notification $notification): void
    {
        if (!$notification->isRead()) {
            $notification->setRead(true);
            $notification->setReadAt(new \DateTime());
            $this->entityManager->flush();
        }
    }

    /**
     * Marquer toutes les notifications d'un utilisateur comme lues
     */
    public function markAllAsRead(User $user): void
    {
        $unreadNotifications = $this->entityManager
            ->getRepository(Notification::class)
            ->findBy([
                'user' => $user,
                'isRead' => false
            ]);

        foreach ($unreadNotifications as $notification) {
            $notification->setRead(true);
            $notification->setReadAt(new \DateTime());
        }

        $this->entityManager->flush();
    }

    /**
     * Obtenir le nombre de notifications non lues
     */
    public function getUnreadCount(User $user): int
    {
        return $this->entityManager
            ->getRepository(Notification::class)
            ->count([
                'user' => $user,
                'isRead' => false
            ]);
    }

    /**
     * Supprimer les anciennes notifications (plus de 30 jours)
     */
    public function cleanOldNotifications(): int
    {
        $thirtyDaysAgo = new \DateTime('-30 days');

        $qb = $this->entityManager->createQueryBuilder();
        $query = $qb->delete(Notification::class, 'n')
            ->where('n.createdAt < :date')
            ->andWhere('n.isRead = true')
            ->setParameter('date', $thirtyDaysAgo)
            ->getQuery();

        $deletedCount = $query->execute();

        $this->logger->info('Old notifications cleaned', [
            'count' => $deletedCount
        ]);

        return $deletedCount;
    }

    /**
     * Créer des notifications prédéfinies
     */
    public function notifyWelcome(User $user): Notification
    {
        return $this->createNotification(
            $user,
            NotificationType::SYSTEM,
            'Bienvenue sur TOUTLUX !',
            'Nous sommes ravis de vous accueillir. Complétez votre profil pour augmenter votre score de confiance.',
            ['action' => 'complete_profile']
        );
    }

    public function notifyDocumentValidated(User $user, string $documentType): Notification
    {
        return $this->createNotification(
            $user,
            NotificationType::DOCUMENT_VALIDATED,
            'Document validé',
            sprintf('Votre document %s a été validé avec succès.', $documentType),
            ['document_type' => $documentType]
        );
    }

    public function notifyDocumentRejected(User $user, string $documentType, string $reason): Notification
    {
        return $this->createNotification(
            $user,
            NotificationType::DOCUMENT_REJECTED,
            'Document refusé',
            sprintf('Votre document %s a été refusé. Raison : %s', $documentType, $reason),
            [
                'document_type' => $documentType,
                'reason' => $reason
            ]
        );
    }

    public function notifyNewMessage(User $recipient, User $sender, string $preview): Notification
    {
        return $this->createNotification(
            $recipient,
            NotificationType::MESSAGE,
            'Nouveau message',
            sprintf('%s vous a envoyé un message : %s', $sender->getFullName(), $preview),
            [
                'sender_id' => $sender->getId(),
                'sender_name' => $sender->getFullName()
            ]
        );
    }

    public function notifyPropertyInterest(User $owner, User $interested, int $propertyId): Notification
    {
        return $this->createNotification(
            $owner,
            NotificationType::PROPERTY,
            'Intérêt pour votre propriété',
            sprintf('%s est intéressé par votre propriété.', $interested->getFullName()),
            [
                'interested_user_id' => $interested->getId(),
                'property_id' => $propertyId
            ]
        );
    }

    public function notifyAdminNewDocument(User $admin, User $user, string $documentType): Notification
    {
        return $this->createNotification(
            $admin,
            NotificationType::SYSTEM,
            'Nouveau document à valider',
            sprintf('%s a soumis un document %s à valider.', $user->getFullName(), $documentType),
            [
                'user_id' => $user->getId(),
                'document_type' => $documentType,
                'action' => 'validate_document'
            ]
        );
    }
}
