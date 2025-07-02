<?php

namespace App\Repository;

use App\Entity\Property;
use App\Entity\User;
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
            ->andWhere('p.available = true')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find featured properties
     */
    public function findFeatured(int $limit = 6): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.featured = true')
            ->andWhere('p.available = true')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find properties by owner
     */
    public function findByOwner(User $owner): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find similar properties
     */
    public function findSimilar(Property $property, int $limit = 4): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id != :id')
            ->andWhere('p.type = :type')
            ->andWhere('p.city = :city')
            ->andWhere('p.available = true')
            ->andWhere('p.price BETWEEN :minPrice AND :maxPrice')
            ->setParameter('id', $property->getId())
            ->setParameter('type', $property->getType())
            ->setParameter('city', $property->getCity())
            ->setParameter('minPrice', $property->getPrice() * 0.8)
            ->setParameter('maxPrice', $property->getPrice() * 1.2)
            ->orderBy('ABS(p.price - :price)', 'ASC')
            ->setParameter('price', $property->getPrice())
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search properties with filters
     */
    public function searchWithFilters(array $criteria): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.images', 'i')
            ->andWhere('p.available = true');

        // Type filter
        if (!empty($criteria['type'])) {
            $qb->andWhere('p.type = :type')
                ->setParameter('type', $criteria['type']);
        }

        // City filter
        if (!empty($criteria['city'])) {
            $qb->andWhere('p.city LIKE :city')
                ->setParameter('city', '%' . $criteria['city'] . '%');
        }

        // Price range
        if (!empty($criteria['minPrice'])) {
            $qb->andWhere('p.price >= :minPrice')
                ->setParameter('minPrice', $criteria['minPrice']);
        }

        if (!empty($criteria['maxPrice'])) {
            $qb->andWhere('p.price <= :maxPrice')
                ->setParameter('maxPrice', $criteria['maxPrice']);
        }

        // Surface range
        if (!empty($criteria['minSurface'])) {
            $qb->andWhere('p.surface >= :minSurface')
                ->setParameter('minSurface', $criteria['minSurface']);
        }

        if (!empty($criteria['maxSurface'])) {
            $qb->andWhere('p.surface <= :maxSurface')
                ->setParameter('maxSurface', $criteria['maxSurface']);
        }

        // Rooms
        if (!empty($criteria['minRooms'])) {
            $qb->andWhere('p.rooms >= :minRooms')
                ->setParameter('minRooms', $criteria['minRooms']);
        }

        // Bedrooms
        if (!empty($criteria['minBedrooms'])) {
            $qb->andWhere('p.bedrooms >= :minBedrooms')
                ->setParameter('minBedrooms', $criteria['minBedrooms']);
        }

        // Features
        if (!empty($criteria['features']) && is_array($criteria['features'])) {
            foreach ($criteria['features'] as $index => $feature) {
                $qb->andWhere('JSON_CONTAINS(p.features, :feature' . $index . ') = 1')
                    ->setParameter('feature' . $index, json_encode($feature));
            }
        }

        // Sorting
        $sortField = $criteria['sort'] ?? 'createdAt';
        $sortOrder = $criteria['order'] ?? 'DESC';
        $qb->orderBy('p.' . $sortField, $sortOrder);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find properties in radius
     */
    public function findInRadius(float $latitude, float $longitude, float $radius = 10): array
    {
        // Using Haversine formula for distance calculation
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
            WHERE p.available = 1
            HAVING distance < :radius
            ORDER BY distance ASC
        ';

        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius' => $radius
        ]);

        $ids = array_column($resultSet->fetchAllAssociative(), 'id');

        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get property statistics
     */
    public function getStatistics(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT
                COUNT(*) as total_properties,
                COUNT(CASE WHEN available = 1 THEN 1 END) as available_properties,
                COUNT(CASE WHEN type = "sale" THEN 1 END) as for_sale,
                COUNT(CASE WHEN type = "rent" THEN 1 END) as for_rent,
                AVG(CAST(price AS DECIMAL(10,2))) as avg_price,
                AVG(surface) as avg_surface,
                SUM(view_count) as total_views
            FROM property
        ';

        return $conn->fetchAssociative($sql);
    }

    /**
     * Get popular properties
     */
    public function findMostViewed(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.available = true')
            ->orderBy('p.viewCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get recent properties
     */
    public function findRecent(int $days = 7, int $limit = 10): array
    {
        $date = new \DateTimeImmutable('-' . $days . ' days');

        return $this->createQueryBuilder('p')
            ->andWhere('p.available = true')
            ->andWhere('p.createdAt >= :date')
            ->setParameter('date', $date)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Update property statistics
     */
    public function incrementViewCount(Property $property): void
    {
        $this->createQueryBuilder('p')
            ->update()
            ->set('p.viewCount', 'p.viewCount + 1')
            ->where('p.id = :id')
            ->setParameter('id', $property->getId())
            ->getQuery()
            ->execute();
    }
}
