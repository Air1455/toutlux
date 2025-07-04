<?php

namespace App\Serializer;

use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use App\Entity\User;

/**
 * Ajoute des groupes de serialisation basés sur l'utilisateur actuel
 */
final class UserContextBuilder implements SerializerContextBuilderInterface
{
    public function __construct(
        private SerializerContextBuilderInterface $decorated,
        private AuthorizationCheckerInterface $authorizationChecker
    ) {}

    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);

        $resourceClass = $context['resource_class'] ?? null;

        if (!$resourceClass) {
            return $context;
        }

        // Ajouter des groupes en fonction des permissions
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $context['groups'][] = 'admin:read';
            if (!$normalization) {
                $context['groups'][] = 'admin:write';
            }
        }

        // Pour l'entité User, ajouter des groupes spécifiques
        if ($resourceClass === User::class) {
            if ($this->authorizationChecker->isGranted('ROLE_USER')) {
                $context['groups'][] = 'user:detail';
            }
        }

        // Groupes pour la sécurité
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $context['groups'][] = 'authenticated';
        }

        return $context;
    }
}
