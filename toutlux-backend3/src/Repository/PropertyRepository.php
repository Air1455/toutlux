<?php

namespace App\Repository;

use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Property>
 *
 * @method Property|null find($id, $lockMode = null, $lockVersion = null)
 * @method Property|null findOneBy(array $criteria, array $orderBy = null)
 * @method Property[]    findAll()
 * @method Property[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PropertyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Property::class);
    }

    /**
     * Find available properties
     */
    public function findAvailable(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', Property::STATUS_AVAILABLE)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find properties by city
     */
    public function findByCity(string $city): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.city = :city')
            ->setParameter('city', $city)
            ->orderBy('p.price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find properties by price range
     */
    public function findByPriceRange(float $min, float $max, string $type = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.price >= :min')
            ->andWhere('p.price <= :max')
            ->setParameter('min', $min)
            ->setParameter('max', $max);

        if ($type) {
            $qb->andWhere('p.type = :type')
                ->setParameter('type', $type);
        }

        return $qb->orderBy('p.price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search properties
     */
    public function search(array $criteria): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', Property::STATUS_AVAILABLE);

        if (!empty($criteria['city'])) {
            $qb->andWhere('p.city LIKE :city')
                ->setParameter('city', '%' . $criteria['city'] . '%');
        }

        if (!empty($criteria['type'])) {
            $qb->andWhere('p.type = :type')
                ->setParameter('type', $criteria['type']);
        }

        if (!empty($criteria['minPrice'])) {
            $qb->andWhere('p.price >= :minPrice')
                ->setParameter('minPrice', $criteria['minPrice']);
        }

        if (!empty($criteria['maxPrice'])) {
            $qb->andWhere('p.price <= :maxPrice')
                ->setParameter('maxPrice', $criteria['maxPrice']);
        }

        if (!empty($criteria['minSurface'])) {
            $qb->andWhere('p.surface >= :minSurface')
                ->setParameter('minSurface', $criteria['minSurface']);
        }

        if (!empty($criteria['maxSurface'])) {
            $qb->andWhere('p.surface <= :maxSurface')
                ->setParameter('maxSurface', $criteria['maxSurface']);
        }

        if (!empty($criteria['rooms'])) {
            $qb->andWhere('p.rooms >= :rooms')
                ->setParameter('rooms', $criteria['rooms']);
        }

        if (!empty($criteria['bedrooms'])) {
            $qb->andWhere('p.bedrooms >= :bedrooms')
                ->setParameter('bedrooms', $criteria['bedrooms']);
        }

        return $qb->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find properties near coordinates
     */
    public function findNearCoordinates(float $latitude, float $longitude, float $radius = 10): array
    {
        // Haversine formula for distance calculation
        $sql = '
            SELECT p.*, (
                6371 * acos(
                    cos(radians(:latitude)) *
                    cos(radians(p.latitude)) *
                    cos(radians(p.longitude) - radians(:longitude)) +
                    sin(radians(:latitude)) *
                    sin(radians(p.latitude))
                )
            ) AS distance
            FROM property p
            WHERE p.latitude IS NOT NULL
            AND p.longitude IS NOT NULL
            HAVING distance < :radius
            ORDER BY distance ASC
        ';

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $result = $stmt->executeQuery([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius' => $radius
        ]);

        $properties = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $properties[] = $this->find($row['id']);
        }

        return $properties;
    }

    /**
     * Get most viewed properties
     */
    public function findMostViewed(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', Property::STATUS_AVAILABLE)
            ->orderBy('p.viewCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count properties by status
     */
    public function countByStatus(): array
    {
        $qb = $this->createQueryBuilder('p');

        return [
            'available' => (int) $qb->select('COUNT(p.id)')
                ->where('p.status = :status')
                ->setParameter('status', Property::STATUS_AVAILABLE)
                ->getQuery()
                ->getSingleScalarResult(),
            'sold' => (int) $qb->select('COUNT(p.id)')
                ->where('p.status = :status')
                ->setParameter('status', Property::STATUS_SOLD)
                ->getQuery()
                ->getSingleScalarResult(),
            'rented' => (int) $qb->select('COUNT(p.id)')
                ->where('p.status = :status')
                ->setParameter('status', Property::STATUS_RENTED)
                ->getQuery()
                ->getSingleScalarResult()
        ];
    }
}
