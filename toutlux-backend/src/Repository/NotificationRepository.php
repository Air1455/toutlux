<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 *
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Find notifications for user
     */
    public function findByUser(User $user, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find unread notifications for user
     */
    public function findUnreadByUser(User $user, int $limit = null): array
    {
        $qb = $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->orderBy('n.priority', 'DESC')
            ->addOrderBy('n.createdAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count unread notifications
     */
    public function countUnreadByUser(User $user): int
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find notifications by type
     */
    public function findByUserAndType(User $user, string $type, int $limit = 20): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->andWhere('n.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Mark notifications as read
     */
    public function markAsRead(array $notificationIds, User $user): int
    {
        return $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', 'true')
            ->set('n.readAt', ':now')
            ->where('n.id IN (:ids)')
            ->andWhere('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('ids', $notificationIds)
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Mark all as read for user
     */
    public function markAllAsRead(User $user): int
    {
        return $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', 'true')
            ->set('n.readAt', ':now')
            ->where('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Find notifications by priority
     */
    public function findByUserAndPriority(User $user, string $priority, int $limit = 20): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->andWhere('n.priority = :priority')
            ->setParameter('user', $user)
            ->setParameter('priority', $priority)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get notification statistics for user
     */
    public function getUserNotificationStats(User $user): array
    {
        $stats = $this->createQueryBuilder('n')
            ->select('n.type, COUNT(n.id) as count, SUM(CASE WHEN n.isRead = false THEN 1 ELSE 0 END) as unread')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->groupBy('n.type')
            ->getQuery()
            ->getResult();

        $total = 0;
        $totalUnread = 0;
        $byType = [];

        foreach ($stats as $stat) {
            $count = (int) $stat['count'];
            $unread = (int) $stat['unread'];

            $total += $count;
            $totalUnread += $unread;

            $byType[$stat['type']] = [
                'total' => $count,
                'unread' => $unread,
                'read' => $count - $unread
            ];
        }

        return [
            'total' => $total,
            'unread' => $totalUnread,
            'read' => $total - $totalUnread,
            'by_type' => $byType
        ];
    }

    /**
     * Clean up old read notifications
     */
    public function cleanupOldRead(int $daysOld = 30): int
    {
        $date = new \DateTimeImmutable('-' . $daysOld . ' days');

        return $this->createQueryBuilder('n')
            ->delete()
            ->andWhere('n.isRead = true')
            ->andWhere('n.readAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }

    /**
     * Find similar recent notifications to avoid duplicates
     */
    public function findSimilarRecent(User $user, string $type, string $title, int $hoursAgo = 24): array
    {
        $since = new \DateTimeImmutable('-' . $hoursAgo . ' hours');

        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->andWhere('n.type = :type')
            ->andWhere('n.title = :title')
            ->andWhere('n.createdAt > :since')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->setParameter('title', $title)
            ->setParameter('since', $since)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
