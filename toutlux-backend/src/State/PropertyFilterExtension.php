<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Property;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Extension pour les filtres avancés sur Property
 * Compatible API Platform 4.1 + Symfony 7.3
 */
final class PropertyFilterExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private RequestStack $requestStack
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = []
    ): void {
        if (Property::class !== $resourceClass) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        // Filtre géographique intelligent
        if ($request->query->has('latitude') && $request->query->has('longitude')) {
            $this->addGeoFilter(
                $queryBuilder,
                $queryNameGenerator,
                $alias,
                (float) $request->query->get('latitude'),
                (float) $request->query->get('longitude'),
                (float) $request->query->get('radius', 10) // 10km par défaut
            );
        }

        // Filtre par caractéristiques (features)
        if ($request->query->has('features')) {
            $features = $request->query->all('features');
            if (is_array($features) && !empty($features)) {
                $this->addFeaturesFilter($queryBuilder, $queryNameGenerator, $alias, $features);
            }
        }

        // Filtre budget intelligent (+/- 20%)
        if ($request->query->has('budget')) {
            $budget = (float) $request->query->get('budget');
            if ($budget > 0) {
                $this->addBudgetFilter($queryBuilder, $queryNameGenerator, $alias, $budget);
            }
        }

        // Filtre par distance depuis une adresse
        if ($request->query->has('near') && $request->query->has('distance')) {
            $address = $request->query->get('near');
            $distance = (float) $request->query->get('distance', 5);
            // Ici vous pourriez intégrer un service de géocodage pour convertir l'adresse en coordonnées
        }

        // Filtre par type de bien et surface
        if ($request->query->has('property_type_surface')) {
            $this->addPropertyTypeSurfaceFilter($queryBuilder, $queryNameGenerator, $alias, $request);
        }
    }

    private function addGeoFilter(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $alias,
        float $latitude,
        float $longitude,
        float $radius
    ): void {
        $latParam = $queryNameGenerator->generateParameterName('latitude');
        $lngParam = $queryNameGenerator->generateParameterName('longitude');
        $radiusParam = $queryNameGenerator->generateParameterName('radius');

        // Formule haversine pour calcul de distance
        $queryBuilder
            ->andWhere(sprintf(
                '(6371 * ACOS(COS(RADIANS(:%s)) * COS(RADIANS(%s.latitude)) * COS(RADIANS(%s.longitude) - RADIANS(:%s)) + SIN(RADIANS(:%s)) * SIN(RADIANS(%s.latitude)))) <= :%s',
                $latParam, $alias, $alias, $lngParam, $latParam, $alias, $radiusParam
            ))
            ->setParameter($latParam, $latitude)
            ->setParameter($lngParam, $longitude)
            ->setParameter($radiusParam, $radius);
    }

    private function addFeaturesFilter(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $alias,
        array $features
    ): void {
        $conditions = [];

        foreach ($features as $index => $feature) {
            $param = $queryNameGenerator->generateParameterName('feature_' . $index);
            $conditions[] = sprintf('JSON_CONTAINS(%s.features, :%s)', $alias, $param);
            $queryBuilder->setParameter($param, json_encode($feature));
        }

        if (!empty($conditions)) {
            $queryBuilder->andWhere(implode(' AND ', $conditions));
        }
    }

    private function addBudgetFilter(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $alias,
        float $budget
    ): void {
        $minParam = $queryNameGenerator->generateParameterName('minPrice');
        $maxParam = $queryNameGenerator->generateParameterName('maxPrice');

        $minPrice = $budget * 0.8; // -20%
        $maxPrice = $budget * 1.2; // +20%

        $queryBuilder
            ->andWhere(sprintf('%s.price BETWEEN :%s AND :%s', $alias, $minParam, $maxParam))
            ->setParameter($minParam, $minPrice)
            ->setParameter($maxParam, $maxPrice);
    }

    private function addPropertyTypeSurfaceFilter(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $alias,
        $request
    ): void {
        // Exemple de filtre combiné intelligent
        $minSurface = $request->query->get('min_surface');
        $maxSurface = $request->query->get('max_surface');
        $propertyType = $request->query->get('type');

        if ($minSurface !== null) {
            $minSurfaceParam = $queryNameGenerator->generateParameterName('minSurface');
            $queryBuilder
                ->andWhere(sprintf('%s.surface >= :%s', $alias, $minSurfaceParam))
                ->setParameter($minSurfaceParam, (int) $minSurface);
        }

        if ($maxSurface !== null) {
            $maxSurfaceParam = $queryNameGenerator->generateParameterName('maxSurface');
            $queryBuilder
                ->andWhere(sprintf('%s.surface <= :%s', $alias, $maxSurfaceParam))
                ->setParameter($maxSurfaceParam, (int) $maxSurface);
        }
    }
}
