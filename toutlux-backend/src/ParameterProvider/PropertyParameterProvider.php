<?php

namespace App\ParameterProvider;

use ApiPlatform\State\ParameterProviderInterface;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\HttpFoundation\Request;

/**
 * Parameter Provider pour les filtres avancés sur Property
 * Nouvelle fonctionnalité API Platform 4.1
 */
final class PropertyParameterProvider implements ParameterProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $context['request'] ?? null;

        if (!$request instanceof Request) {
            return [];
        }

        $parameters = [];

        // Gérer la recherche géographique
        if ($request->query->has('latitude') && $request->query->has('longitude')) {
            $parameters['geo_search'] = [
                'latitude' => (float) $request->query->get('latitude'),
                'longitude' => (float) $request->query->get('longitude'),
                'radius' => (float) $request->query->get('radius', 10)
            ];
        }

        // Gérer la recherche par features
        if ($request->query->has('features')) {
            $features = $request->query->all('features');
            if (is_array($features)) {
                $parameters['features'] = $features;
            }
        }

        // Gérer les filtres de prix intelligents
        if ($request->query->has('budget')) {
            $budget = (float) $request->query->get('budget');
            $parameters['price_range'] = [
                'min' => $budget * 0.8,
                'max' => $budget * 1.2
            ];
        }

        return $parameters;
    }
}
