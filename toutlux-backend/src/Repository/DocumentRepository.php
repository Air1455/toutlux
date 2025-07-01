<?php

namespace App\Repository;

use App\Entity\Document;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 *
 * @method Document|null find($id, $lockMode = null, $lockVersion = null)
 * @method Document|null findOneBy(array $criteria, array $orderBy = null)
 * @method Document[]    findAll()
 * @method Document[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
     * Find pending documents
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.status = :status')
            ->setParameter('status', Document::STATUS_PENDING)
            ->orderBy('d.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find user documents by type
     */
    public function findUserDocumentsByType(User $user, string $type): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.user = :user')
            ->andWhere('d.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if user has approved document of type
     */
    public function hasApprovedDocument(User $user, string $type): bool
    {
        $count = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.user = :user')
            ->andWhere('d.type = :type')
            ->andWhere('d.status = :status')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->setParameter('status', Document::STATUS_APPROVED)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Count documents by status
     */
    public function countByStatus(): array
    {
        $qb = $this->createQueryBuilder('d');

        return [
            'pending' => (int) $qb->select('COUNT(d.id)')
                ->where('d.status = :status')
                ->setParameter('status', Document::STATUS_PENDING)
                ->getQuery()
                ->getSingleScalarResult(),
            'approved' => (int) $qb->select('COUNT(d.id)')
                ->where('d.status = :status')
                ->setParameter('status', Document::STATUS_APPROVED)
                ->getQuery()
                ->getSingleScalarResult(),
            'rejected' => (int) $qb->select('COUNT(d.id)')
                ->where('d.status = :status')
                ->setParameter('status', Document::STATUS_REJECTED)
                ->getQuery()
                ->getSingleScalarResult()
        ];
    }

    /**
     * Find documents validated between dates
     */
    public function findValidatedBetween(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.validatedAt >= :start')
            ->andWhere('d.validatedAt <= :end')
            ->andWhere('d.status IN (:statuses)')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('statuses', [Document::STATUS_APPROVED, Document::STATUS_REJECTED])
            ->orderBy('d.validatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
