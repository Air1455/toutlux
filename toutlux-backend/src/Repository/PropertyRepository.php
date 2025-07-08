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

    /**
     * Trouve les villes uniques
     */
    public function findUniqueCities(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('DISTINCT p.city')
            ->where('p.city IS NOT NULL')
            ->orderBy('p.city', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'city');
    }

    /**
     * Trouve les propriétés pour l'export avec filtres avancés
     */
    public function findForExport(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.owner', 'o')
            ->addSelect('o');

        // IDs spécifiques (pour export de sélection)
        if (!empty($filters['ids'])) {
            $ids = is_string($filters['ids']) ? explode(',', $filters['ids']) : $filters['ids'];
            $qb->andWhere('p.id IN (:ids)')
                ->setParameter('ids', array_filter($ids));

            return $qb->orderBy('p.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        }

        // Recherche textuelle
        if (!empty($filters['search'])) {
            $qb->andWhere('p.title LIKE :search OR p.description LIKE :search OR p.city LIKE :search OR o.firstName LIKE :search OR o.lastName LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Filtre par type
        if (!empty($filters['type'])) {
            $qb->andWhere('p.type = :type')
                ->setParameter('type', $filters['type']);
        }

        // Filtre disponibilité
        if (isset($filters['available']) && $filters['available'] !== '') {
            $qb->andWhere('p.available = :available')
                ->setParameter('available', $filters['available'] === '1');
        }

        // Filtre vérifié
        if (isset($filters['verified']) && $filters['verified'] !== '') {
            $qb->andWhere('p.verified = :verified')
                ->setParameter('verified', $filters['verified'] === '1');
        }

        // Filtre en vedette
        if (isset($filters['featured']) && $filters['featured'] !== '') {
            $qb->andWhere('p.featured = :featured')
                ->setParameter('featured', $filters['featured'] === '1');
        }

        // Filtre par ville
        if (!empty($filters['city'])) {
            $qb->andWhere('p.city = :city')
                ->setParameter('city', $filters['city']);
        }

        // Filtres de prix
        if (!empty($filters['price_min'])) {
            $qb->andWhere('p.price >= :priceMin')
                ->setParameter('priceMin', $filters['price_min']);
        }

        if (!empty($filters['price_max'])) {
            $qb->andWhere('p.price <= :priceMax')
                ->setParameter('priceMax', $filters['price_max']);
        }

        // Filtres de surface
        if (!empty($filters['surface_min'])) {
            $qb->andWhere('p.surface >= :surfaceMin')
                ->setParameter('surfaceMin', $filters['surface_min']);
        }

        if (!empty($filters['surface_max'])) {
            $qb->andWhere('p.surface <= :surfaceMax')
                ->setParameter('surfaceMax', $filters['surface_max']);
        }

        // Filtre nombre de pièces minimum
        if (!empty($filters['rooms_min'])) {
            $qb->andWhere('p.rooms >= :roomsMin')
                ->setParameter('roomsMin', $filters['rooms_min']);
        }

        return $qb->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche avec critères et pagination
     */
    public function search(string $searchTerm, array $criteria = [], int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.owner', 'o')
            ->addSelect('o')
            ->where('p.title LIKE :search OR p.description LIKE :search OR p.city LIKE :search')
            ->setParameter('search', '%' . $searchTerm . '%');

        foreach ($criteria as $field => $value) {
            $qb->andWhere("p.$field = :$field")
                ->setParameter($field, $value);
        }

        return $qb->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les résultats de recherche
     */
    public function countSearch(string $searchTerm, array $criteria = []): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.title LIKE :search OR p.description LIKE :search OR p.city LIKE :search')
            ->setParameter('search', '%' . $searchTerm . '%');

        foreach ($criteria as $field => $value) {
            $qb->andWhere("p.$field = :$field")
                ->setParameter($field, $value);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Obtient les statistiques générales
     */
    public function getStatistics(): array
    {
        // Total propriétés
        $total = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Propriétés disponibles
        $available = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.available = true')
            ->getQuery()
            ->getSingleScalarResult();

        // Propriétés vérifiées
        $verified = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.verified = true')
            ->getQuery()
            ->getSingleScalarResult();

        // Propriétés en vedette
        $featured = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.featured = true')
            ->getQuery()
            ->getSingleScalarResult();

        // Total des vues
        $totalViews = $this->createQueryBuilder('p')
            ->select('SUM(p.viewCount)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return [
            'total' => (int) $total,
            'available' => (int) $available,
            'verified' => (int) $verified,
            'featured' => (int) $featured,
            'totalViews' => (int) $totalViews,
        ];
    }

    /**
     * Find properties with advanced filters and pagination
     */
    public function findWithAdvancedFilters(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.owner', 'o')
            ->leftJoin('p.images', 'i')
            ->addSelect('o', 'i');

        // Recherche textuelle
        if (!empty($filters['search'])) {
            $qb->andWhere('p.title LIKE :search OR p.description LIKE :search OR p.city LIKE :search OR o.firstName LIKE :search OR o.lastName LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Filtre par type
        if (!empty($filters['type'])) {
            $qb->andWhere('p.type = :type')
                ->setParameter('type', $filters['type']);
        }

        // Filtre disponibilité
        if (isset($filters['available']) && $filters['available'] !== '') {
            $qb->andWhere('p.available = :available')
                ->setParameter('available', $filters['available'] === '1');
        }

        // Filtre vérifié
        if (isset($filters['verified']) && $filters['verified'] !== '') {
            $qb->andWhere('p.verified = :verified')
                ->setParameter('verified', $filters['verified'] === '1');
        }

        // Filtre en vedette
        if (isset($filters['featured']) && $filters['featured'] !== '') {
            $qb->andWhere('p.featured = :featured')
                ->setParameter('featured', $filters['featured'] === '1');
        }

        // Filtre par ville
        if (!empty($filters['city'])) {
            $qb->andWhere('p.city = :city')
                ->setParameter('city', $filters['city']);
        }

        // Filtres de prix
        if (!empty($filters['price_min'])) {
            $qb->andWhere('p.price >= :priceMin')
                ->setParameter('priceMin', $filters['price_min']);
        }

        if (!empty($filters['price_max'])) {
            $qb->andWhere('p.price <= :priceMax')
                ->setParameter('priceMax', $filters['price_max']);
        }

        // Filtres de surface
        if (!empty($filters['surface_min'])) {
            $qb->andWhere('p.surface >= :surfaceMin')
                ->setParameter('surfaceMin', $filters['surface_min']);
        }

        if (!empty($filters['surface_max'])) {
            $qb->andWhere('p.surface <= :surfaceMax')
                ->setParameter('surfaceMax', $filters['surface_max']);
        }

        // Filtre nombre de pièces minimum
        if (!empty($filters['rooms_min'])) {
            $qb->andWhere('p.rooms >= :roomsMin')
                ->setParameter('roomsMin', $filters['rooms_min']);
        }

        // Compter le total
        $totalQb = clone $qb;
        $total = $totalQb->select('COUNT(DISTINCT p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Appliquer la pagination
        $properties = $qb->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'properties' => $properties,
            'total' => (int) $total,
            'totalPages' => (int) ceil($total / $limit),
        ];
    }

    /**
     * Export par batch pour optimiser la mémoire sur de gros exports
     */
    public function findForExportBatch(array $filters = [], int $offset = 0, int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.owner', 'o')
            ->addSelect('o');

        // IDs spécifiques (pour export de sélection)
        if (!empty($filters['ids'])) {
            $ids = is_string($filters['ids']) ? explode(',', $filters['ids']) : $filters['ids'];
            $qb->andWhere('p.id IN (:ids)')
                ->setParameter('ids', array_filter($ids));

            return $qb->orderBy('p.createdAt', 'DESC')
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        }

        // Appliquer les mêmes filtres que findForExport
        // Recherche textuelle
        if (!empty($filters['search'])) {
            $qb->andWhere('p.title LIKE :search OR p.description LIKE :search OR p.city LIKE :search OR o.firstName LIKE :search OR o.lastName LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Filtre par type
        if (!empty($filters['type'])) {
            $qb->andWhere('p.type = :type')
                ->setParameter('type', $filters['type']);
        }

        // Filtre disponibilité
        if (isset($filters['available']) && $filters['available'] !== '') {
            $qb->andWhere('p.available = :available')
                ->setParameter('available', $filters['available'] === '1');
        }

        // Filtre vérifié
        if (isset($filters['verified']) && $filters['verified'] !== '') {
            $qb->andWhere('p.verified = :verified')
                ->setParameter('verified', $filters['verified'] === '1');
        }

        // Filtre en vedette
        if (isset($filters['featured']) && $filters['featured'] !== '') {
            $qb->andWhere('p.featured = :featured')
                ->setParameter('featured', $filters['featured'] === '1');
        }

        // Filtre par ville
        if (!empty($filters['city'])) {
            $qb->andWhere('p.city = :city')
                ->setParameter('city', $filters['city']);
        }

        // Filtres de prix
        if (!empty($filters['price_min'])) {
            $qb->andWhere('p.price >= :priceMin')
                ->setParameter('priceMin', $filters['price_min']);
        }

        if (!empty($filters['price_max'])) {
            $qb->andWhere('p.price <= :priceMax')
                ->setParameter('priceMax', $filters['price_max']);
        }

        // Filtres de surface
        if (!empty($filters['surface_min'])) {
            $qb->andWhere('p.surface >= :surfaceMin')
                ->setParameter('surfaceMin', $filters['surface_min']);
        }

        if (!empty($filters['surface_max'])) {
            $qb->andWhere('p.surface <= :surfaceMax')
                ->setParameter('surfaceMax', $filters['surface_max']);
        }

        // Filtre nombre de pièces minimum
        if (!empty($filters['rooms_min'])) {
            $qb->andWhere('p.rooms >= :roomsMin')
                ->setParameter('roomsMin', $filters['rooms_min']);
        }

        return $qb->orderBy('p.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
