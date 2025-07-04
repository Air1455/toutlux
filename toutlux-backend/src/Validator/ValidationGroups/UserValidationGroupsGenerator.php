<?php

namespace App\Validator\ValidationGroups;

use App\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Génère dynamiquement les groupes de validation pour l'entité User
 */
class UserValidationGroupsGenerator
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker
    ) {}

    /**
     * Génère les groupes de validation en fonction du contexte
     */
    public function __invoke(User $user): array
    {
        $groups = ['Default'];

        // Si c'est une nouvelle entité
        if (!$user->getId()) {
            $groups[] = 'user:register';
        } else {
            $groups[] = 'user:update';
        }

        // Si l'utilisateur met à jour son profil
        if ($this->authorizationChecker->isGranted('ROLE_USER')) {
            $groups[] = 'user:profile';
        }

        // Si c'est un admin qui modifie
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $groups[] = 'admin:edit';
        }

        // Groupes basés sur l'état du profil
        if (!$user->isVerified()) {
            $groups[] = 'user:unverified';
        }

        if (!$user->isProfileCompleted()) {
            $groups[] = 'user:incomplete';
        }

        return $groups;
    }
}
