<?php

namespace App\Service\Notification;

use App\Entity\Notification;
use App\Entity\User;
use App\Entity\Message;
use App\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function createNotification(
        User $user,
        string $type,
        string $title,
        string $content,
        array $data = []
    ): Notification {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType($type);
        $notification->setTitle($title);
        $notification->setContent($content);
        $notification->setData($data);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    public function createWelcomeNotification(User $user): Notification
    {
        return $this->createNotification(
            $user,
            Notification::TYPE_WELCOME,
            'Welcome to Real Estate App!',
            'Thank you for joining us. Complete your profile to get started.',
            []
        );
    }

    public function createDocumentApprovedNotification(User $user, Document $document): Notification
    {
        return $this->createNotification(
            $user,
            Notification::TYPE_DOCUMENT_APPROVED,
            'Document Approved',
            sprintf('Your %s has been approved.', $document->getTypeLabel()),
            [
                'documentId' => $document->getId()->toRfc4122(),
                'documentType' => $document->getType()
            ]
        );
    }

    public function createMessageReceivedNotification(User $user, Message $message): Notification
    {
        return $this->createNotification(
            $user,
            Notification::TYPE_MESSAGE_RECEIVED,
            'New Message',
            sprintf(
                'You have a new message from %s',
                $message->getSender()->getFullName() ?? $message->getSender()->getEmail()
            ),
            [
                'messageId' => $message->getId()->toRfc4122(),
                'senderId' => $message->getSender()->getId()->toRfc4122()
            ]
        );
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
        $this->entityManager->flush();
    }

    public function markAllAsRead(User $user): void
    {
        $unreadNotifications = $this->entityManager->getRepository(Notification::class)
            ->findBy(['user' => $user, 'isRead' => false]);

        foreach ($unreadNotifications as $notification) {
            $notification->markAsRead();
        }

        $this->entityManager->flush();
    }

    public function deleteNotification(Notification $notification): void
    {
        $this->entityManager->remove($notification);
        $this->entityManager->flush();
    }

    public function getUnreadCount(User $user): int
    {
        return $this->entityManager->getRepository(Notification::class)
            ->count(['user' => $user, 'isRead' => false]);
    }

    public function getUserNotifications(User $user, int $limit = 20, int $offset = 0): array
    {
        return $this->entityManager->getRepository(Notification::class)
            ->findBy(
                ['user' => $user],
                ['createdAt' => 'DESC'],
                $limit,
                $offset
            );
    }

    public function createProfileIncompleteNotification(User $user, array $missingFields): Notification
    {
        $fieldLabels = [
            'personal_info' => 'personal information',
            'identity' => 'identity documents',
            'financial' => 'financial documents',
            'terms' => 'terms and conditions'
        ];

        $missingLabels = array_map(
            fn($field) => $fieldLabels[$field] ?? $field,
            $missingFields
        );

        $content = sprintf(
            'Please complete the following to increase your trust score: %s',
            implode(', ', $missingLabels)
        );

        return $this->createNotification(
            $user,
            Notification::TYPE_PROFILE_INCOMPLETE,
            'Complete Your Profile',
            $content,
            ['missingFields' => $missingFields]
        );
    }

    public function createTrustScoreUpdateNotification(User $user, float $oldScore, float $newScore): Notification
    {
        return $this->createNotification(
            $user,
            Notification::TYPE_TRUST_SCORE_UPDATE,
            'Trust Score Updated',
            sprintf(
                'Your trust score has %s from %.1f to %.1f stars!',
                $newScore > $oldScore ? 'increased' : 'decreased',
                $oldScore,
                $newScore
            ),
            [
                'oldScore' => $oldScore,
                'newScore' => $newScore
            ]
        );
    }

    public function cleanupOldNotifications(int $daysToKeep = 30): int
    {
        $cutoffDate = new \DateTime();
        $cutoffDate->modify("-{$daysToKeep} days");

        $qb = $this->entityManager->createQueryBuilder();
        $query = $qb->delete(Notification::class, 'n')
            ->where('n.createdAt < :cutoffDate')
            ->andWhere('n.isRead = :isRead')
            ->setParameter('cutoffDate', $cutoffDate)
            ->setParameter('isRead', true)
            ->getQuery();

        return $query->execute();
    }
}
