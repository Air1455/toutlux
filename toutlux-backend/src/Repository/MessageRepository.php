<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\Property;
use App\Entity\User;
use App\Enum\MessageStatus;
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
     * Find conversations for a user
     */
    public function findUserConversations(User $user, int $limit = 20, int $offset = 0): array
    {
        // Subquery to get the latest message for each conversation
        $subQuery = $this->createQueryBuilder('m2')
            ->select('MAX(m2.createdAt)')
            ->where('(m2.sender = :user AND m2.recipient = m.recipient) OR (m2.recipient = :user AND m2.sender = m.sender)')
            ->andWhere('m2.deletedBySender = false OR m2.deletedByRecipient = false')
            ->getDQL();

        $qb = $this->createQueryBuilder('m')
            ->where('(m.sender = :user OR m.recipient = :user)')
            ->andWhere('m.createdAt = (' . $subQuery . ')')
            ->andWhere('(m.sender = :user AND m.deletedBySender = false) OR (m.recipient = :user AND m.deletedByRecipient = false)')
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find conversation between two users
     */
    public function findConversationBetween(User $user1, User $user2, ?Property $property = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('(m.sender = :user1 AND m.recipient = :user2) OR (m.sender = :user2 AND m.recipient = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->andWhere('(m.sender = :user1 AND m.deletedBySender = false) OR (m.recipient = :user1 AND m.deletedByRecipient = false)')
            ->orderBy('m.createdAt', 'ASC');

        if ($property) {
            $qb->andWhere('m.property = :property')
                ->setParameter('property', $property);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find unread messages for a user
     */
    public function findUnreadByUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.recipient = :user')
            ->andWhere('m.isRead = false')
            ->andWhere('m.status = :status')
            ->andWhere('m.deletedByRecipient = false')
            ->setParameter('user', $user)
            ->setParameter('status', MessageStatus::APPROVED)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count unread messages
     */
    public function countUnreadByUser(User $user): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.recipient = :user')
            ->andWhere('m.isRead = false')
            ->andWhere('m.status = :status')
            ->andWhere('m.deletedByRecipient = false')
            ->setParameter('user', $user)
            ->setParameter('status', MessageStatus::APPROVED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Search user messages
     */
    public function searchUserMessages(User $user, string $query, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')
            ->leftJoin('m.recipient', 'r')
            ->leftJoin('m.property', 'p')
            ->where('(m.sender = :user OR m.recipient = :user)')
            ->andWhere('(m.sender = :user AND m.deletedBySender = false) OR (m.recipient = :user AND m.deletedByRecipient = false)')
            ->setParameter('user', $user);

        // Search in content, sender name, recipient name
        if (!empty($query)) {
            $qb->andWhere('(m.content LIKE :query OR m.subject LIKE :query OR s.firstName LIKE :query OR s.lastName LIKE :query OR r.firstName LIKE :query OR r.lastName LIKE :query)')
                ->setParameter('query', '%' . $query . '%');
        }

        // Filter by read status
        if (isset($filters['isRead'])) {
            $qb->andWhere('m.isRead = :isRead')
                ->setParameter('isRead', $filters['isRead']);
        }

        // Filter by property
        if (isset($filters['property'])) {
            $qb->andWhere('m.property = :property')
                ->setParameter('property', $filters['property']);
        }

        // Filter by date range
        if (isset($filters['dateFrom'])) {
            $qb->andWhere('m.createdAt >= :dateFrom')
                ->setParameter('dateFrom', $filters['dateFrom']);
        }

        if (isset($filters['dateTo'])) {
            $qb->andWhere('m.createdAt <= :dateTo')
                ->setParameter('dateTo', $filters['dateTo']);
        }

        return $qb->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find messages by property
     */
    public function findByProperty(Property $property): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.property = :property')
            ->setParameter('property', $property)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find messages pending moderation
     */
    public function findPendingModeration(int $limit = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.status = :status')
            ->setParameter('status', MessageStatus::PENDING)
            ->orderBy('m.createdAt', 'ASC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count messages by status
     */
    public function countByStatus(MessageStatus $status): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count messages with property
     */
    public function countMessagesWithProperty(): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.property IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get message statistics for a user
     */
    public function getUserMessageStats(User $user): array
    {
        $sent = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.sender = :user')
            ->andWhere('m.deletedBySender = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $received = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.recipient = :user')
            ->andWhere('m.deletedByRecipient = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $unread = $this->countUnreadByUser($user);

        return [
            'sent' => $sent,
            'received' => $received,
            'unread' => $unread
        ];
    }

    /**
     * Mark conversation as read
     */
    public function markConversationAsRead(User $reader, User $otherUser): int
    {
        return $this->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', 'true')
            ->set('m.readAt', ':now')
            ->where('m.recipient = :reader')
            ->andWhere('m.sender = :otherUser')
            ->andWhere('m.isRead = false')
            ->setParameter('reader', $reader)
            ->setParameter('otherUser', $otherUser)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Archive old messages
     */
    public function archiveOldMessages(int $daysOld = 365): int
    {
        $date = new \DateTimeImmutable('-' . $daysOld . ' days');

        return $this->createQueryBuilder('m')
            ->update()
            ->set('m.archivedBySender', 'true')
            ->set('m.archivedByRecipient', 'true')
            ->where('m.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }

    /**
     * Find messages by status
     */
    public function findByStatus(MessageStatus $status, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.status = :status')
            ->setParameter('status', $status)
            ->orderBy('m.createdAt', 'ASC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }
}
