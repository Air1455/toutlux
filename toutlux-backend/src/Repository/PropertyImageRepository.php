<?php

namespace App\Repository;

use App\Entity\Property;
use App\Entity\PropertyImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PropertyImage>
 *
 * @method PropertyImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method PropertyImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method PropertyImage[]    findAll()
 * @method PropertyImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PropertyImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyImage::class);
    }

    /**
     * Find main image for a property
     */
    public function findMainImage(Property $property): ?PropertyImage
    {
        return $this->createQueryBuilder('pi')
            ->andWhere('pi.property = :property')
            ->andWhere('pi.isMain = true')
            ->setParameter('property', $property)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find images by property ordered by position
     */
    public function findByPropertyOrdered(Property $property): array
    {
        return $this->createQueryBuilder('pi')
            ->andWhere('pi.property = :property')
            ->setParameter('property', $property)
            ->orderBy('pi.isMain', 'DESC')
            ->addOrderBy('pi.position', 'ASC')
            ->addOrderBy('pi.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Update positions for property images
     */
    public function updatePositions(Property $property, array $positions): void
    {
        foreach ($positions as $imageId => $position) {
            $this->createQueryBuilder('pi')
                ->update()
                ->set('pi.position', ':position')
                ->where('pi.id = :id')
                ->andWhere('pi.property = :property')
                ->setParameter('position', $position)
                ->setParameter('id', $imageId)
                ->setParameter('property', $property)
                ->getQuery()
                ->execute();
        }
    }

    /**
     * Set main image for property
     */
    public function setMainImage(PropertyImage $image): void
    {
        // Remove main flag from all images of this property
        $this->createQueryBuilder('pi')
            ->update()
            ->set('pi.isMain', 'false')
            ->where('pi.property = :property')
            ->setParameter('property', $image->getProperty())
            ->getQuery()
            ->execute();

        // Set main flag on selected image
        $image->setIsMain(true);
        $this->getEntityManager()->persist($image);
        $this->getEntityManager()->flush();
    }

    /**
     * Get next position for property
     */
    public function getNextPosition(Property $property): int
    {
        $result = $this->createQueryBuilder('pi')
            ->select('MAX(pi.position)')
            ->andWhere('pi.property = :property')
            ->setParameter('property', $property)
            ->getQuery()
            ->getSingleScalarResult();

        return ($result ?? 0) + 1;
    }

    /**
     * Count images by property
     */
    public function countByProperty(Property $property): int
    {
        return $this->createQueryBuilder('pi')
            ->select('COUNT(pi.id)')
            ->andWhere('pi.property = :property')
            ->setParameter('property', $property)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get total size of images for property
     */
    public function getTotalSizeByProperty(Property $property): int
    {
        $result = $this->createQueryBuilder('pi')
            ->select('SUM(pi.imageSize)')
            ->andWhere('pi.property = :property')
            ->setParameter('property', $property)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    /**
     * Find orphaned images (without property)
     */
    public function findOrphaned(): array
    {
        return $this->createQueryBuilder('pi')
            ->andWhere('pi.property IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * Clean up orphaned images older than specified days
     */
    public function cleanupOrphaned(int $daysOld = 7): int
    {
        $date = new \DateTimeImmutable('-' . $daysOld . ' days');

        return $this->createQueryBuilder('pi')
            ->delete()
            ->andWhere('pi.property IS NULL')
            ->andWhere('pi.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}
