<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Find user by email (case insensitive)
     */
    public function findByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('LOWER(u.email) = LOWER(:email)')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find user by Google ID
     */
    public function findByGoogleId(string $googleId): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.googleId = :googleId')
            ->setParameter('googleId', $googleId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find users with unverified emails
     * Fixed: Use isEmailVerified instead of emailVerified
     */
    public function findUnverifiedUsers(\DateTimeInterface $before = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.isEmailVerified = false');

        if ($before) {
            $qb->andWhere('u.createdAt < :before')
                ->setParameter('before', $before);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find users with incomplete profiles
     * Fixed: Use isEmailVerified instead of emailVerified
     */
    public function findIncompleteProfiles(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.profileCompleted = false')
            ->andWhere('u.isEmailVerified = true')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users with pending documents
     */
    public function findUsersWithPendingDocuments(): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.documents', 'd')
            ->andWhere('d.status = :status')
            ->setParameter('status', 'pending')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users by role
     * Fixed: Use JSON_CONTAINS for proper JSON searching
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('JSON_CONTAINS(u.roles, :role) = 1')
            ->setParameter('role', json_encode($role))
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search users by name, email or phone
     * Fixed: Include phone search
     */
    public function searchUsers(string $query): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email LIKE :query OR u.firstName LIKE :query OR u.lastName LIKE :query OR u.phone LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users for export with filters
     * New method needed by the controller
     */
    public function findUsersForExport(?string $search = null, ?string $status = null, ?string $role = null, ?string $trustScore = null): array
    {
        $qb = $this->createQueryBuilder('u');

        if ($search) {
            $qb->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search OR u.phone LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($status === 'active') {
            $qb->andWhere('u.active = true');
        } elseif ($status === 'inactive') {
            $qb->andWhere('u.active = false');
        }

        if ($role) {
            $qb->andWhere('JSON_CONTAINS(u.roles, :role) = 1')
                ->setParameter('role', json_encode($role));
        }

        if ($trustScore) {
            $qb->andWhere('CAST(u.trustScore AS DECIMAL(3,2)) >= :trustScore')
                ->setParameter('trustScore', (float) $trustScore);
        }

        return $qb->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get user statistics
     * Fixed: Column names to match entity properties
     */
    public function getUserStatistics(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
        SELECT
            COUNT(*) as total_users,
            COUNT(CASE WHEN is_email_verified = 1 THEN 1 END) as verified_users,
            COUNT(CASE WHEN profile_completed = 1 THEN 1 END) as completed_profiles,
            COUNT(CASE WHEN identity_verified = 1 THEN 1 END) as identity_verified,
            COUNT(CASE WHEN financial_verified = 1 THEN 1 END) as financial_verified,
            AVG(CAST(trust_score AS DECIMAL(3,2))) as avg_trust_score,
            COUNT(CASE WHEN created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_users_30_days,
            COUNT(CASE WHEN created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_users_7_days,
            COUNT(CASE WHEN last_login_at > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as active_users_30_days
        FROM user
        WHERE active = 1
    ';

        $result = $conn->fetchAssociative($sql);

        // Distribution des scores de confiance
        $trustScoreSql = '
        SELECT
            CASE
                WHEN CAST(trust_score AS DECIMAL(3,2)) < 1 THEN "0-1"
                WHEN CAST(trust_score AS DECIMAL(3,2)) < 2 THEN "1-2"
                WHEN CAST(trust_score AS DECIMAL(3,2)) < 3 THEN "2-3"
                WHEN CAST(trust_score AS DECIMAL(3,2)) < 4 THEN "3-4"
                ELSE "4-5"
            END as score_range,
            COUNT(*) as count
        FROM user
        WHERE active = 1
        GROUP BY score_range
        ORDER BY MIN(CAST(trust_score AS DECIMAL(3,2)))
    ';

        $result['trust_score_distribution'] = $conn->fetchAllAssociative($trustScoreSql);

        // Utilisateurs par source
        $sourceSql = '
        SELECT
            COUNT(CASE WHEN google_id IS NULL THEN 1 END) as email_users,
            COUNT(CASE WHEN google_id IS NOT NULL THEN 1 END) as google_users
        FROM user
        WHERE active = 1
    ';

        $sourceStats = $conn->fetchAssociative($sourceSql);
        $result['by_source'] = $sourceStats;

        return $result;
    }

    /**
     * Find users with expiring documents
     */
    public function findUsersWithExpiringDocuments(int $daysBefore = 30): array
    {
        $expirationDate = new \DateTimeImmutable('+' . $daysBefore . ' days');

        return $this->createQueryBuilder('u')
            ->innerJoin('u.documents', 'd')
            ->andWhere('d.expiresAt IS NOT NULL')
            ->andWhere('d.expiresAt < :expirationDate')
            ->andWhere('d.expiresAt > :now')
            ->andWhere('d.status = :status')
            ->setParameter('expirationDate', $expirationDate)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('status', 'approved')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    /**
     * Update trust scores for all users
     */
    public function updateAllTrustScores(): void
    {
        $users = $this->findAll();

        foreach ($users as $user) {
            $user->calculateTrustScore();
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Clean up unverified users
     * Fixed: Use isEmailVerified instead of emailVerified
     */
    public function cleanupUnverifiedUsers(int $daysOld = 7): int
    {
        $date = new \DateTimeImmutable('-' . $daysOld . ' days');

        return $this->createQueryBuilder('u')
            ->delete()
            ->andWhere('u.isEmailVerified = false')
            ->andWhere('u.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }

    /**
     * Get paginated users with filters
     * Additional method for better pagination
     */
    public function findUsersWithFilters(
        ?string $search = null,
        ?string $status = null,
        ?string $role = null,
        ?float $trustScore = null,
        int $page = 1,
        int $limit = 20
    ): array {
        $qb = $this->createQueryBuilder('u');

        if ($search) {
            $qb->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search OR u.phone LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($status === 'active') {
            $qb->andWhere('u.active = true');
        } elseif ($status === 'inactive') {
            $qb->andWhere('u.active = false');
        }

        if ($role) {
            $qb->andWhere('JSON_CONTAINS(u.roles, :role) = 1')
                ->setParameter('role', json_encode($role));
        }

        if ($trustScore) {
            $qb->andWhere('CAST(u.trustScore AS DECIMAL(3,2)) >= :trustScore')
                ->setParameter('trustScore', $trustScore);
        }

        $totalQuery = clone $qb;
        $total = $totalQuery->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

        $users = $qb->orderBy('u.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'users' => $users,
            'total' => $total,
            'totalPages' => ceil($total / $limit)
        ];
    }
}
