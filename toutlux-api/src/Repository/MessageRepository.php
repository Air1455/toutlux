<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUnreadByUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->andWhere('m.isRead = false')
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countUnreadByUser(User $user): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.user = :user')
            ->andWhere('m.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->addSelect('u')
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUnreadForAdmin(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->addSelect('u')
            ->andWhere('m.isRead = false')
            ->andWhere('m.type = :type')
            ->setParameter('type', 'user_to_admin')
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
