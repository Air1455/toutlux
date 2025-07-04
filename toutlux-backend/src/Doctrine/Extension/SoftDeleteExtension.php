<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

/**
 * Extension pour gérer le soft delete automatiquement
 */
final class SoftDeleteExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    private array $softDeleteableEntities = [
        // Ajouter ici les entités qui supportent le soft delete
        // Pour l'instant, aucune entité n'a de soft delete dans notre schéma
    ];

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->addSoftDeleteFilter($queryBuilder, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->addSoftDeleteFilter($queryBuilder, $resourceClass);
    }

    private function addSoftDeleteFilter(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        // Si l'entité ne supporte pas le soft delete, ne rien faire
        if (!in_array($resourceClass, $this->softDeleteableEntities)) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        // Ajouter la condition pour exclure les entités supprimées
        $queryBuilder->andWhere(sprintf('%s.deletedAt IS NULL', $rootAlias));
    }
}
