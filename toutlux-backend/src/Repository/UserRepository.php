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
     * Find users by role
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"' . $role . '"%')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find verified users
     */
    public function findVerifiedUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.isVerified = :verified')
            ->setParameter('verified', true)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users with complete profiles
     */
    public function findUsersWithCompleteProfiles(): array
    {
        return $this->createQueryBuilder('u')
            ->join('u.profile', 'p')
            ->where('p.personalInfoValidated = :validated')
            ->andWhere('p.identityValidated = :validated')
            ->andWhere('p.financialValidated = :validated')
            ->andWhere('p.termsAccepted = :validated')
            ->setParameter('validated', true)
            ->orderBy('u.trustScore', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users by trust score range
     */
    public function findByTrustScoreRange(float $min, float $max): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.trustScore >= :min')
            ->andWhere('u.trustScore <= :max')
            ->setParameter('min', $min)
            ->setParameter('max', $max)
            ->orderBy('u.trustScore', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count users by verification status
     */
    public function countByVerificationStatus(): array
    {
        $qb = $this->createQueryBuilder('u');

        return [
            'verified' => (int) $qb->select('COUNT(u.id)')
                ->where('u.isVerified = true')
                ->getQuery()
                ->getSingleScalarResult(),
            'unverified' => (int) $qb->select('COUNT(u.id)')
                ->where('u.isVerified = false')
                ->getQuery()
                ->getSingleScalarResult()
        ];
    }

    /**
     * Find users registered between dates
     */
    public function findRegisteredBetween(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.createdAt >= :start')
            ->andWhere('u.createdAt <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
