<?php

namespace App\Repository;

use App\Entity\Document;
use App\Entity\User;
use App\Enum\DocumentStatus;
use App\Enum\DocumentType;
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
     * Find documents by user and type
     */
    public function findByUserAndType(User $user, string $type): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :user')
            ->andWhere('d.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find validated documents by user
     */
    public function findValidatedByUser(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :user')
            ->andWhere('d.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', DocumentStatus::APPROVED)
            ->orderBy('d.validatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pending documents
     */
    public function findPending(int $limit = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.status = :status')
            ->setParameter('status', DocumentStatus::PENDING)
            ->orderBy('d.createdAt', 'ASC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find expiring documents
     */
    public function findExpiring(\DateTimeInterface $before): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.expiresAt IS NOT NULL')
            ->andWhere('d.expiresAt < :before')
            ->andWhere('d.expiresAt > :now')
            ->andWhere('d.status = :status')
            ->setParameter('before', $before)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('status', DocumentStatus::APPROVED)
            ->orderBy('d.expiresAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count documents by status
     */
    public function countByStatus(DocumentStatus $status): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get statistics by type and status
     */
    public function getStatsByTypeAndStatus(): array
    {
        return $this->createQueryBuilder('d')
            ->select('d.type, d.status, COUNT(d.id) as count')
            ->groupBy('d.type, d.status')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find documents validated by a specific admin
     */
    public function findValidatedBy(User $admin, int $limit = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.validatedBy = :admin')
            ->setParameter('admin', $admin)
            ->orderBy('d.validatedAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get user document summary
     */
    public function getUserDocumentSummary(User $user): array
    {
        $results = $this->createQueryBuilder('d')
            ->select('d.type, d.subType, d.status, COUNT(d.id) as count')
            ->andWhere('d.user = :user')
            ->setParameter('user', $user)
            ->groupBy('d.type, d.subType, d.status')
            ->getQuery()
            ->getResult();

        $summary = [
            'identity' => [
                'total' => 0,
                'approved' => 0,
                'pending' => 0,
                'rejected' => 0
            ],
            'financial' => [
                'total' => 0,
                'approved' => 0,
                'pending' => 0,
                'rejected' => 0
            ]
        ];

        foreach ($results as $result) {
            $type = $result['type']->value;
            $status = $result['status']->value;
            $count = $result['count'];

            if (isset($summary[$type])) {
                $summary[$type]['total'] += $count;
                $summary[$type][$status] = ($summary[$type][$status] ?? 0) + $count;
            }
        }

        return $summary;
    }

    /**
     * Clean up old rejected documents
     */
    public function deleteOldRejected(int $daysOld = 30): int
    {
        $date = new \DateTimeImmutable('-' . $daysOld . ' days');

        return $this->createQueryBuilder('d')
            ->delete()
            ->andWhere('d.status = :status')
            ->andWhere('d.createdAt < :date')
            ->setParameter('status', DocumentStatus::REJECTED)
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }

    /**
     * Find documents by status
     */
    public function findByStatus(DocumentStatus $status, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.status = :status')
            ->setParameter('status', $status)
            ->orderBy('d.createdAt', 'ASC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }
}
