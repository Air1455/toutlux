<?php

namespace App\Repository;

use App\Entity\EmailLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EmailLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailLog::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingEmails(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('e.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findFailedEmails(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->setParameter('status', 'failed')
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
