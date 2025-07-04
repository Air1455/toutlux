<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Document;
use App\Entity\Message;
use App\Entity\Notification;
use App\Entity\Property;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Extension Doctrine pour filtrer automatiquement les données par utilisateur courant
 */
final class CurrentUserExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private Security $security
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        $user = $this->security->getUser();

        // Si pas d'utilisateur connecté ou si admin, ne pas filtrer
        if (!$user instanceof User || $this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        switch ($resourceClass) {
            case Document::class:
                // Les utilisateurs ne voient que leurs propres documents
                $queryBuilder->andWhere(sprintf('%s.user = :current_user', $rootAlias));
                $queryBuilder->setParameter('current_user', $user);
                break;

            case Message::class:
                // Les utilisateurs voient les messages qu'ils ont envoyés ou reçus
                $queryBuilder->andWhere(
                    sprintf('(%s.sender = :current_user OR %s.recipient = :current_user)', $rootAlias, $rootAlias)
                );
                $queryBuilder->setParameter('current_user', $user);

                // Exclure les messages supprimés
                $queryBuilder->andWhere(
                    sprintf(
                        '((%s.sender = :current_user AND %s.deletedBySender = false) OR (%s.recipient = :current_user AND %s.deletedByRecipient = false))',
                        $rootAlias,
                        $rootAlias,
                        $rootAlias,
                        $rootAlias
                    )
                );
                break;

            case Notification::class:
                // Les utilisateurs ne voient que leurs propres notifications
                $queryBuilder->andWhere(sprintf('%s.user = :current_user', $rootAlias));
                $queryBuilder->setParameter('current_user', $user);
                break;

            case Property::class:
                // Pour les propriétés, on applique un filtre seulement pour certaines opérations
                $operationName = $operation?->getName();

                // Si c'est une opération de mise à jour/suppression, vérifier le propriétaire
                if (in_array($operationName, ['put', 'patch', 'delete'])) {
                    $queryBuilder->andWhere(sprintf('%s.owner = :current_user', $rootAlias));
                    $queryBuilder->setParameter('current_user', $user);
                }
                break;
        }
    }
}
