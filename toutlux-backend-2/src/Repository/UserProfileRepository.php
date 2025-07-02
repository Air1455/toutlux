<?php

namespace App\Repository;

use App\Entity\UserProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserProfile>
 *
 * @method UserProfile|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserProfile|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserProfile[]    findAll()
 * @method UserProfile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserProfile::class);
    }

    /**
     * Find incomplete profiles
     */
    public function findIncompleteProfiles(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.personalInfoValidated = :false OR p.identityValidated = :false OR p.financialValidated = :false OR p.termsAccepted = :false')
            ->setParameter('false', false)
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find profiles by completion percentage
     */
    public function findByCompletionPercentage(int $min, int $max = 100): array
    {
        $profiles = $this->findAll();
        $filtered = [];

        foreach ($profiles as $profile) {
            $percentage = $profile->getCompletionPercentage();
            if ($percentage >= $min && $percentage <= $max) {
                $filtered[] = $profile;
            }
        }

        return $filtered;
    }
}
