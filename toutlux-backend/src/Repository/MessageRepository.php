<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 *
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Find messages for a user (sent or received)
     */
    public function findUserMessages(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.sender = :user OR m.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find unread messages for a user
     */
    public function findUnreadMessages(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.recipient = :user')
            ->andWhere('m.isRead = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count unread messages for a user
     */
    public function countUnreadMessages(User $user): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.recipient = :user')
            ->andWhere('m.isRead = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find conversation between two users
     */
    public function findConversation(User $user1, User $user2): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :user1 AND m.recipient = :user2) OR (m.sender = :user2 AND m.recipient = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find messages by status
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.status = :status')
            ->setParameter('status', $status)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find messages pending moderation
     */
    public function findPendingModeration(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.status = :status')
            ->setParameter('status', Message::STATUS_PENDING)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Mark all messages as read for a user
     */
    public function markAllAsRead(User $user): void
    {
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', ':isRead')
            ->where('m.recipient = :user')
            ->andWhere('m.isRead = :currentRead')
            ->setParameter('isRead', true)
            ->setParameter('user', $user)
            ->setParameter('currentRead', false)
            ->getQuery()
            ->execute();
    }

    /**
     * Delete old messages
     */
    public function deleteOldMessages(\DateTimeInterface $before): int
    {
        return $this->createQueryBuilder('m')
            ->delete()
            ->where('m.createdAt < :before')
            ->andWhere('m.status != :pending')
            ->setParameter('before', $before)
            ->setParameter('pending', Message::STATUS_PENDING)
            ->getQuery()
            ->execute();
    }
}
