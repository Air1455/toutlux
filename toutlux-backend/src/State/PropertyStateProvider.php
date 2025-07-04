<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Property;
use App\Repository\PropertyRepository;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * State Provider pour les opérations de lecture sur Property
 */
final class PropertyStateProvider implements ProviderInterface
{
    public function __construct(
        private PropertyRepository $propertyRepository,
        private Security $security
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // Pour une collection
        if (!isset($uriVariables['id'])) {
            $filters = $context['filters'] ?? [];

            // Appliquer les filtres de recherche
            if (!empty($filters)) {
                return $this->propertyRepository->searchWithFilters($filters);
            }

            // Retourner uniquement les propriétés disponibles pour les utilisateurs non-admin
            if (!$this->security->isGranted('ROLE_ADMIN')) {
                return $this->propertyRepository->findAvailable();
            }

            return $this->propertyRepository->findBy([], ['createdAt' => 'DESC']);
        }

        // Pour un item spécifique
        $property = $this->propertyRepository->find($uriVariables['id']);

        if (!$property) {
            return null;
        }

        // Incrémenter le compteur de vues (sauf pour le propriétaire et admin)
        $currentUser = $this->security->getUser();
        if (!$currentUser ||
            ($property->getOwner() !== $currentUser && !$this->security->isGranted('ROLE_ADMIN'))) {
            $this->propertyRepository->incrementViewCount($property);
        }

        return $property;
    }
}
