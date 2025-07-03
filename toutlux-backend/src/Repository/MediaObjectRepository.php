<?php

namespace App\Repository;

use App\Entity\MediaObject;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MediaObject>
 *
 * @method MediaObject|null find($id, $lockMode = null, $lockVersion = null)
 * @method MediaObject|null findOneBy(array $criteria, array $orderBy = null)
 * @method MediaObject[]    findAll()
 * @method MediaObject[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MediaObjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaObject::class);
    }

    /**
     * Find media objects by owner
     */
    public function findByOwner(User $owner, int $limit = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('m.createdAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find media objects by mime type
     */
    public function findByMimeType(string $mimeType): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.mimeType = :mimeType')
            ->setParameter('mimeType', $mimeType)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find images
     */
    public function findImages(User $owner = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.mimeType LIKE :imageType')
            ->setParameter('imageType', 'image/%');

        if ($owner) {
            $qb->andWhere('m.owner = :owner')
                ->setParameter('owner', $owner);
        }

        return $qb->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find videos
     */
    public function findVideos(User $owner = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.mimeType LIKE :videoType')
            ->setParameter('videoType', 'video/%');

        if ($owner) {
            $qb->andWhere('m.owner = :owner')
                ->setParameter('owner', $owner);
        }

        return $qb->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find PDFs
     */
    public function findPdfs(User $owner = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.mimeType = :pdfType')
            ->setParameter('pdfType', 'application/pdf');

        if ($owner) {
            $qb->andWhere('m.owner = :owner')
                ->setParameter('owner', $owner);
        }

        return $qb->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total size by owner
     */
    public function getTotalSizeByOwner(User $owner): int
    {
        $result = $this->createQueryBuilder('m')
            ->select('SUM(m.size)')
            ->andWhere('m.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    /**
     * Count by owner
     */
    public function countByOwner(User $owner): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get media statistics
     */
    public function getStatistics(User $owner = null): array
    {
        $qb = $this->createQueryBuilder('m');

        if ($owner) {
            $qb->andWhere('m.owner = :owner')
                ->setParameter('owner', $owner);
        }

        $totalCount = (clone $qb)
            ->select('COUNT(m.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalSize = (clone $qb)
            ->select('SUM(m.size)')
            ->getQuery()
            ->getSingleScalarResult();

        $byType = (clone $qb)
            ->select('m.mimeType, COUNT(m.id) as count, SUM(m.size) as totalSize')
            ->groupBy('m.mimeType')
            ->getQuery()
            ->getResult();

        return [
            'total_count' => (int) $totalCount,
            'total_size' => (int) ($totalSize ?? 0),
            'by_type' => $byType
        ];
    }

    /**
     * Find orphaned media (without owner)
     */
    public function findOrphaned(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.owner IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * Clean up old orphaned media
     */
    public function cleanupOrphaned(int $daysOld = 7): int
    {
        $date = new \DateTimeImmutable('-' . $daysOld . ' days');

        return $this->createQueryBuilder('m')
            ->delete()
            ->andWhere('m.owner IS NULL')
            ->andWhere('m.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }

    /**
     * Search media by filename
     */
    public function searchByFilename(string $query, User $owner = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.originalName LIKE :query OR m.filePath LIKE :query')
            ->setParameter('query', '%' . $query . '%');

        if ($owner) {
            $qb->andWhere('m.owner = :owner')
                ->setParameter('owner', $owner);
        }

        return $qb->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
