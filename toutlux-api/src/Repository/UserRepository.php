<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Trouve les utilisateurs avec des filtres
     */
    public function findWithFilters(array $filters, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('u');

        if (!empty($filters['status'])) {
            $qb->andWhere('u.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['verification'])) {
            switch ($filters['verification']) {
                case 'email_verified':
                    $qb->andWhere('u.isEmailVerified = true');
                    break;
                case 'phone_verified':
                    $qb->andWhere('u.isPhoneVerified = true');
                    break;
                case 'identity_verified':
                    $qb->andWhere('u.isIdentityVerified = true');
                    break;
                case 'fully_verified':
                    $qb->andWhere('u.isEmailVerified = true')
                        ->andWhere('u.isPhoneVerified = true')
                        ->andWhere('u.isIdentityVerified = true')
                        ->andWhere('u.isFinancialDocsVerified = true');
                    break;
                case 'not_verified':
                    $qb->andWhere('u.isEmailVerified = false OR u.isPhoneVerified = false');
                    break;
            }
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['has_documents'])) {
            if ($filters['has_documents'] === 'yes') {
                $qb->andWhere('u.identityCard IS NOT NULL AND u.selfieWithId IS NOT NULL');
            } else {
                $qb->andWhere('u.identityCard IS NULL OR u.selfieWithId IS NULL');
            }
        }

        return $qb->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les utilisateurs par statut
     */
    public function countByStatus(string $status): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les utilisateurs en attente de validation
     */
    public function findPendingValidation(int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.status = :status')
            ->orWhere('u.isEmailVerified = false')
            ->orWhere('u.isPhoneVerified = false')
            ->orWhere('(u.identityCard IS NOT NULL AND u.isIdentityVerified = false)')
            ->orWhere('((u.incomeProof IS NOT NULL OR u.ownershipProof IS NOT NULL) AND u.isFinancialDocsVerified = false)')
            ->setParameter('status', 'pending_verification')
            ->orderBy('u.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les validations récentes
     */
    public function findRecentValidations(int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.emailVerifiedAt IS NOT NULL')
            ->orWhere('u.phoneVerifiedAt IS NOT NULL')
            ->orWhere('u.identityVerifiedAt IS NOT NULL')
            ->orWhere('u.financialDocsVerifiedAt IS NOT NULL')
            ->orderBy('GREATEST(
                COALESCE(u.emailVerifiedAt, \'1970-01-01\'),
                COALESCE(u.phoneVerifiedAt, \'1970-01-01\'),
                COALESCE(u.identityVerifiedAt, \'1970-01-01\'),
                COALESCE(u.financialDocsVerifiedAt, \'1970-01-01\')
            )', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les utilisateurs en attente de validation d'identité
     */
    public function countPendingIdentityValidation(): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.identityCard IS NOT NULL')
            ->andWhere('u.selfieWithId IS NOT NULL')
            ->andWhere('u.isIdentityVerified = false')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les utilisateurs en attente de validation financière
     */
    public function countPendingFinancialValidation(): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('(u.incomeProof IS NOT NULL OR u.ownershipProof IS NOT NULL)')
            ->andWhere('u.isFinancialDocsVerified = false')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les utilisateurs entièrement vérifiés
     */
    public function countFullyVerified(): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isEmailVerified = true')
            ->andWhere('u.isPhoneVerified = true')
            ->andWhere('u.isIdentityVerified = true')
            ->andWhere('u.isFinancialDocsVerified = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Inscriptions du jour
     */
    public function countRegistrationsToday(): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt >= :today')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Inscriptions de la semaine
     */
    public function countRegistrationsThisWeek(): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt >= :weekStart')
            ->setParameter('weekStart', new \DateTimeImmutable('monday this week'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Inscriptions du mois
     */
    public function countRegistrationsThisMonth(): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt >= :monthStart')
            ->setParameter('monthStart', new \DateTimeImmutable('first day of this month'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Temps moyen de complétion du profil
     */
    public function getAverageCompletionTime(): ?float
    {
        $result = $this->createQueryBuilder('u')
            ->select('AVG(
                TIMESTAMPDIFF(DAY, u.createdAt,
                    GREATEST(
                        COALESCE(u.emailVerifiedAt, u.createdAt),
                        COALESCE(u.phoneVerifiedAt, u.createdAt),
                        COALESCE(u.identityVerifiedAt, u.createdAt),
                        COALESCE(u.financialDocsVerifiedAt, u.createdAt)
                    )
                )
            ) as avgDays')
            ->where('u.isEmailVerified = true')
            ->andWhere('u.isPhoneVerified = true')
            ->andWhere('u.isIdentityVerified = true')
            ->andWhere('u.isFinancialDocsVerified = true')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round($result, 1) : null;
    }

    /**
     * Statistiques par type d'utilisateur
     */
    public function getUserTypeStats(): array
    {
        $result = $this->createQueryBuilder('u')
            ->select('u.userType, COUNT(u.id) as count')
            ->where('u.userType IS NOT NULL')
            ->groupBy('u.userType')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($result as $row) {
            $stats[$row['userType']] = (int) $row['count'];
        }

        return $stats;
    }

    /**
     * Statistiques mensuelles d'inscription
     */
    public function getMonthlyRegistrationStats(int $months = 12): array
    {
        $startDate = new \DateTimeImmutable("-{$months} months");

        $result = $this->createQueryBuilder('u')
            ->select('YEAR(u.createdAt) as year, MONTH(u.createdAt) as month, COUNT(u.id) as count')
            ->where('u.createdAt >= :startDate')
            ->setParameter('startDate', $startDate)
            ->groupBy('year, month')
            ->orderBy('year, month')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($result as $row) {
            $monthKey = sprintf('%04d-%02d', $row['year'], $row['month']);
            $stats[$monthKey] = (int) $row['count'];
        }

        return $stats;
    }

    /**
     * Statistiques de vérification
     */
    public function getVerificationStats(): array
    {
        $total = $this->count([]);

        $emailVerified = $this->count(['isEmailVerified' => true]);
        $phoneVerified = $this->count(['isPhoneVerified' => true]);
        $identityVerified = $this->count(['isIdentityVerified' => true]);
        $financialVerified = $this->count(['isFinancialDocsVerified' => true]);

        return [
            'total_users' => $total,
            'email_verified' => $emailVerified,
            'phone_verified' => $phoneVerified,
            'identity_verified' => $identityVerified,
            'financial_verified' => $financialVerified,
            'email_rate' => $total > 0 ? round(($emailVerified / $total) * 100, 2) : 0,
            'phone_rate' => $total > 0 ? round(($phoneVerified / $total) * 100, 2) : 0,
            'identity_rate' => $total > 0 ? round(($identityVerified / $total) * 100, 2) : 0,
            'financial_rate' => $total > 0 ? round(($financialVerified / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Trouve les utilisateurs avec des rejets
     */
    public function findUsersWithRejections(): array
    {
        return $this->createQueryBuilder('u')
            ->where('JSON_EXTRACT(u.metadata, \'$."identity_rejection_reason"\') IS NOT NULL')
            ->orWhere('JSON_EXTRACT(u.metadata, \'$."financial_rejection_reason"\') IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * Utilisateurs récemment actifs
     */
    public function findRecentlyActive(int $days = 7, int $limit = 50): array
    {
        $since = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('u')
            ->where('u.lastActiveAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('u.lastActiveAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Utilisateurs inactifs
     */
    public function findInactiveUsers(int $days = 30, int $limit = 50): array
    {
        $since = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('u')
            ->where('u.lastActiveAt < :since OR u.lastActiveAt IS NULL')
            ->setParameter('since', $since)
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Top utilisateurs par nombre d'annonces
     */
    public function findTopPublishers(int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.houses', 'h')
            ->select('u, COUNT(h.id) as housesCount')
            ->groupBy('u.id')
            ->having('housesCount > 0')
            ->orderBy('housesCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Utilisateurs suspects (critères multiples)
     */
    public function findSuspiciousUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.emailVerificationAttempts > 5')
            ->orWhere('u.status = :banned')
            ->orWhere('JSON_EXTRACT(u.metadata, \'$."admin_flags"\') IS NOT NULL')
            ->setParameter('banned', 'banned')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche avancée d'utilisateurs
     */
    public function advancedSearch(array $criteria): array
    {
        $qb = $this->createQueryBuilder('u');

        // Critères de base
        if (!empty($criteria['email'])) {
            $qb->andWhere('u.email LIKE :email')
                ->setParameter('email', '%' . $criteria['email'] . '%');
        }

        if (!empty($criteria['name'])) {
            $qb->andWhere('u.firstName LIKE :name OR u.lastName LIKE :name')
                ->setParameter('name', '%' . $criteria['name'] . '%');
        }

        if (!empty($criteria['phone'])) {
            $qb->andWhere('u.phoneNumber LIKE :phone')
                ->setParameter('phone', '%' . $criteria['phone'] . '%');
        }

        // Critères de vérification
        if (isset($criteria['email_verified'])) {
            $qb->andWhere('u.isEmailVerified = :emailVerified')
                ->setParameter('emailVerified', $criteria['email_verified']);
        }

        if (isset($criteria['phone_verified'])) {
            $qb->andWhere('u.isPhoneVerified = :phoneVerified')
                ->setParameter('phoneVerified', $criteria['phone_verified']);
        }

        // Critères de date
        if (!empty($criteria['created_after'])) {
            $qb->andWhere('u.createdAt >= :createdAfter')
                ->setParameter('createdAfter', new \DateTimeImmutable($criteria['created_after']));
        }

        if (!empty($criteria['created_before'])) {
            $qb->andWhere('u.createdAt <= :createdBefore')
                ->setParameter('createdBefore', new \DateTimeImmutable($criteria['created_before']));
        }

        // Critères d'activité
        if (!empty($criteria['last_active_after'])) {
            $qb->andWhere('u.lastActiveAt >= :lastActiveAfter')
                ->setParameter('lastActiveAfter', new \DateTimeImmutable($criteria['last_active_after']));
        }

        return $qb->orderBy('u.createdAt', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques de completion des profils
     */
    public function getProfileCompletionStats(): array
    {
        // Cette méthode nécessiterait une logique plus complexe pour calculer
        // le pourcentage de completion de chaque profil
        $users = $this->findAll();
        $stats = [
            '0-25' => 0,
            '26-50' => 0,
            '51-75' => 0,
            '76-100' => 0,
        ];

        foreach ($users as $user) {
            $completion = $user->getCompletionPercentage();
            if ($completion <= 25) {
                $stats['0-25']++;
            } elseif ($completion <= 50) {
                $stats['26-50']++;
            } elseif ($completion <= 75) {
                $stats['51-75']++;
            } else {
                $stats['76-100']++;
            }
        }

        return $stats;
    }

    /**
     * Utilisateurs par pays
     */
    public function getUsersByCountry(): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.houses', 'h')
            ->select('h.country, COUNT(DISTINCT u.id) as userCount')
            ->where('h.country IS NOT NULL')
            ->groupBy('h.country')
            ->orderBy('userCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Export des données utilisateurs pour RGPD
     */
    public function exportUserData(User $user): array
    {
        return [
            'profile' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'phone' => $user->getPhoneNumber(),
                'user_type' => $user->getUserType(),
                'created_at' => $user->getCreatedAt()?->format('c'),
                'last_active' => $user->getLastActiveAt()?->format('c'),
            ],
            'verification_status' => [
                'email_verified' => $user->isEmailVerified(),
                'phone_verified' => $user->isPhoneVerified(),
                'identity_verified' => $user->isIdentityVerified(),
                'financial_verified' => $user->isFinancialDocsVerified(),
            ],
            'metadata' => $user->getMetadata(),
            'houses_count' => $user->getHouses()->count(),
        ];
    }
}
